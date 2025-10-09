<?php

namespace App\Http\Controllers\Share;

use App\Http\Controllers\Controller;
use App\Models\ShareExpense;
use App\Models\ShareExpenseSplit;
use App\Models\ShareTopic;
use App\Models\ShareTopicMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShareExpenseController extends Controller
{
    // GET /api/share/topics/{topic}/expenses
    public function index(Request $request, ShareTopic $topic)
    {
        $this->authorizeTopic($request, $topic);

        $items = ShareExpense::query()
            ->where('topic_id', $topic->id)
            ->orderByDesc('id')
            ->get(['id','description','amount','currency','payer_user_id','date','created_at']);

        return response()->json([
            'data' => [
                'expenses' => $items,
                'status'   => $topic->status, // let UI know if closed
            ],
        ]);
    }

    // POST /api/share/topics/{topic}/expenses
    public function store(Request $request, ShareTopic $topic)
    {
        $this->authorizeTopic($request, $topic);

        if ($topic->status === 'closed') {
            abort(403, 'This topic is closed. You cannot add expenses.');
        }

        $payload = $request->validate([
            'description' => 'required|string|max:255',
            'amount'      => 'required|numeric|min:0.01',
            'date'        => 'nullable|date',
            'notes'       => 'nullable|string',
        ]);

        $userId = (int) $request->user()->id;

        return DB::transaction(function () use ($topic, $payload, $userId) {

            // Ensure there is at least the owner as a member
            if (!ShareTopicMember::where('topic_id', $topic->id)->exists()) {
                ShareTopicMember::create([
                    'topic_id'     => $topic->id,
                    'user_id'      => $topic->owner_user_id,
                    'display_name' => optional($topic->owner)->name ?? 'Owner',
                    'role'         => 'owner',
                    'joined_at'    => now(),
                ]);
            }

            // Create the expense (splits are not authoritative for balances anymore)
            $expense = ShareExpense::create([
                'topic_id'      => $topic->id,
                'payer_user_id' => $userId,
                'description'   => $payload['description'],
                'amount'        => $payload['amount'],           // decimal(12,2)
                'currency'      => $topic->currency,             // e.g. 'USD'
                'date'          => $payload['date'] ?? now()->toDateString(),
                'notes'         => $payload['notes'] ?? null,
            ]);

            /**
             * Optional: keep per-expense splits as a snapshot at creation time.
             * These are no longer used to compute balances, but you can keep them
             * for auditing if you like.
             */
            $members = ShareTopicMember::where('topic_id', $topic->id)
                ->orderBy('id')->get(['id']);
            $count = max(1, $members->count());
            $raw   = $expense->amount / $count;
            $split = number_format($raw, 2, '.', '');

            foreach ($members as $m) {
                ShareExpenseSplit::create([
                    'expense_id'   => $expense->id,
                    'member_id'    => $m->id,
                    'share_amount' => $split,
                ]);
            }

            return response()->json(['data' => ['expense_id' => $expense->id]], 201);
        });
    }

    /**
     * Balances are computed *dynamically* from current members and all expenses.
     * - For each expense: split equally among *current* members (in cents for exactness).
     * - Payer gets +amount and also owes their equal share like everyone else.
     * - Everyone else owes just their equal share.
     * This means adding a member later will immediately re-balance past expenses.
     */
    public function balances(Request $request, ShareTopic $topic)
    {
        $this->authorizeTopic($request, $topic);

        // Current members (id, user_id, display_name)
        $members = ShareTopicMember::where('topic_id', $topic->id)
            ->orderBy('id')
            ->get(['id','user_id','display_name']);

        if ($members->isEmpty()) {
            return response()->json(['data' => [
                'members'   => [],
                'balances'  => [],
                'transfers' => [],
            ]]);
        }

        // Map user_id -> member_id (payer lookup), work in cents to avoid rounding drift
        $memberIdByUserId = $members->pluck('id', 'user_id')->all();  // [user_id => member_id]
        $memberIdsOrdered = $members->pluck('id')->values()->all();   // deterministic order

        // Init net (in cents)
        $netCents = array_fill_keys($memberIdsOrdered, 0);

        // All expenses for the topic
        $expenses = ShareExpense::where('topic_id', $topic->id)
            ->get(['payer_user_id','amount']);

        $nMembers = count($memberIdsOrdered);

        foreach ($expenses as $exp) {
            $amountCents = (int) round(((float) $exp->amount) * 100);

            // Split into equal integer cents with remainder distributed to earliest members
            $base = intdiv($amountCents, $nMembers);
            $rem  = $amountCents % $nMembers;

            // Everyone owes their share
            foreach ($memberIdsOrdered as $idx => $mid) {
                $shareForThisMember = $base + ($idx < $rem ? 1 : 0); // distribute remainder
                $netCents[$mid] -= $shareForThisMember;
            }

            // Payer receives the full amount
            $payerUserId = (int) $exp->payer_user_id;
            if (isset($memberIdByUserId[$payerUserId])) {
                $payerMid = $memberIdByUserId[$payerUserId];
                $netCents[$payerMid] += $amountCents;
            }
            // If payer isn't a member (shouldn't happen), we simply don't credit anyone.
        }

        // Build balances in decimal dollars
        $balances = [];
        foreach ($memberIdsOrdered as $mid) {
            $balances[] = [
                'member_id'    => $mid,
                'display_name' => optional($members->firstWhere('id', $mid))->display_name ?? ("Member #{$mid}"),
                'net'          => round($netCents[$mid] / 100, 2),
            ];
        }

        // Suggested transfers to settle (greedy)
        $debtors = [];
        $creditors = [];
        foreach ($balances as $b) {
            if ($b['net'] < -0.005) $debtors[]  = ['member_id' => $b['member_id'], 'amount' => round(-$b['net'], 2)];
            if ($b['net'] >  0.005) $creditors[] = ['member_id' => $b['member_id'], 'amount' => round( $b['net'], 2)];
        }
        usort($debtors,   fn($a,$b) => $b['amount'] <=> $a['amount']);
        usort($creditors, fn($a,$b) => $b['amount'] <=> $a['amount']);

        $transfers = [];
        $i = 0; $j = 0;
        while ($i < count($debtors) && $j < count($creditors)) {
            $pay = min($debtors[$i]['amount'], $creditors[$j]['amount']);
            $transfers[] = [
                'from_member_id' => $debtors[$i]['member_id'],
                'to_member_id'   => $creditors[$j]['member_id'],
                'amount'         => round($pay, 2),
            ];
            $debtors[$i]['amount']   = round($debtors[$i]['amount']   - $pay, 2);
            $creditors[$j]['amount'] = round($creditors[$j]['amount'] - $pay, 2);
            if ($debtors[$i]['amount']   <= 0.005) $i++;
            if ($creditors[$j]['amount'] <= 0.005) $j++;
        }

        return response()->json(['data' => [
            'members'   => $members->map(fn($m) => ['id'=>$m->id, 'display_name'=>$m->display_name])->values(),
            'balances'  => $balances,
            'transfers' => $transfers,
        ]]);
    }

    // DELETE /api/share/topics/{topic}/expenses/{expense}
    public function destroy(Request $request, ShareTopic $topic, ShareExpense $expense)
    {
        // Must belong to this topic
        abort_if($expense->topic_id !== $topic->id, 404, 'Expense not found in this topic.');

        // Only owner or payer can delete
        $uid     = (int) $request->user()->id;
        $isOwner = $topic->owner_user_id === $uid;
        $isPayer = $expense->payer_user_id === $uid;
        abort_if(!$isOwner && !$isPayer, 403, 'Only the topic owner or the payer can delete this expense.');

        DB::transaction(function () use ($expense) {
            ShareExpenseSplit::where('expense_id', $expense->id)->delete();
            $expense->delete();
        });

        return response()->json(['data' => ['deleted' => true]]);
    }

    // --- helpers ---
    private function authorizeTopic(Request $request, ShareTopic $topic): void
    {
        $uid = (int) $request->user()->id;

        $isOwner = ($topic->owner_user_id === $uid);
        $isMember = ShareTopicMember::where('topic_id', $topic->id)
            ->where('user_id', $uid)
            ->exists();

        abort_if(!$isOwner && !$isMember, 403, 'You do not have access to this topic.');
    }
}
