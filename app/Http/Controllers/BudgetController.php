<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Expense;
use Illuminate\Http\Request;
use Carbon\Carbon;

class BudgetController extends Controller
{
    // List budgets (optionally by month=YYYY-MM-01)
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $query = Budget::where('user_id', $userId)
            ->orderByDesc('month')
            ->orderBy('category');

        if ($request->filled('month')) {
            $month = Carbon::parse($request->get('month'))->startOfMonth()->toDateString();
            $query->where('month', $month);
        }

        return $query->get();
    }

    public function store(Request $request)
    {
        $userId = $request->user()->id;

        $data = $request->validate([
            'category'       => ['required','string','max:100'],
            'month'          => ['required','date'],
            'amount_decimal' => ['required','numeric','min:0.01'],
            'currency'       => ['nullable','string','size:3'],
        ]);

        $data['month']    = Carbon::parse($data['month'])->startOfMonth()->toDateString();
        $data['currency'] = $data['currency'] ?? 'USD';

        $exists = Budget::where('user_id', $userId)
            ->where('category', $data['category'])
            ->where('month', $data['month'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Budget already exists for this category and month'], 422);
        }

        $budget = Budget::create([
            'user_id'        => $userId,
            'category'       => $data['category'],
            'month'          => $data['month'],
            'amount_decimal' => $data['amount_decimal'],
            'currency'       => $data['currency'],
        ]);

        return response()->json($budget, 201);
    }

    public function show(Request $request, Budget $budget)
    {
        $this->authorizeBudget($request, $budget);
        return $budget;
    }

    public function update(Request $request, Budget $budget)
    {
        $this->authorizeBudget($request, $budget);

        $data = $request->validate([
            'category'       => ['sometimes','string','max:100'],
            'month'          => ['sometimes','date'],
            'amount_decimal' => ['sometimes','numeric','min:0.01'],
            'currency'       => ['sometimes','string','size:3'],
        ]);

        if (isset($data['month'])) {
            $data['month'] = Carbon::parse($data['month'])->startOfMonth()->toDateString();
        }

        $newCategory = $data['category'] ?? $budget->category;
        $newMonth    = $data['month'] ?? $budget->month->toDateString();

        $exists = Budget::where('user_id', $budget->user_id)
            ->where('category', $newCategory)
            ->where('month', $newMonth)
            ->where('id', '!=', $budget->id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Budget already exists for this category and month'], 422);
        }

        $budget->update($data);
        return $budget;
    }

    public function destroy(Request $request, Budget $budget)
    {
        $this->authorizeBudget($request, $budget);
        $budget->delete();
        return response()->noContent();
    }

    // === Used by the badges in the Expenses table ===
    // GET /api/budgets/stats?month=YYYY-MM-01
    public function stats(Request $request)
    {
        $userId = $request->user()->id;

        $monthStart = Carbon::parse($request->get('month', now()->startOfMonth()))
            ->startOfMonth();
        $monthEnd   = (clone $monthStart)->endOfMonth();

        // Spend by category for that month
        $spendRows = Expense::where('user_id', $userId)
            ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->selectRaw('category, SUM(amount) AS spend')
            ->groupBy('category')
            ->get()
            ->keyBy('category');

        // Budgets for that month
        $budgets = Budget::where('user_id', $userId)
            ->where('month', $monthStart->toDateString())
            ->get();

        $out = [];
        foreach ($budgets as $b) {
            $spend  = (float) ($spendRows[$b->category]->spend ?? 0);
            $amount = (float) $b->amount_decimal;
            $pct    = $amount > 0 ? ($spend / $amount) * 100 : 0.0;
            $status = $pct < 80 ? 'Under' : ($pct <= 100 ? 'Near' : 'Over');

            $out[] = [
                'category' => $b->category,
                'month'    => $b->month->toDateString(),
                'budget'   => $amount,
                'currency' => $b->currency,
                'spend'    => $spend,
                'pct'      => round($pct, 1),
                'status'   => $status,
            ];
        }

        return $out;
    }

    private function authorizeBudget(Request $request, Budget $budget): void
    {
        if ($budget->user_id !== $request->user()->id) {
            abort(403);
        }
    }
}
