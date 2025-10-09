<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Services\InsightService;
use App\Services\AdviceService;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Budget;

class HelperController extends Controller
{
    public function advice(Request $request, InsightService $insights, AdviceService $advice)
    {
        $user = $request->user();
        $userId = $user ? $user->id : null;

        $from = $request->input('date_from') ?: now()->startOfWeek()->toDateString();
        $to   = $request->input('date_to')   ?: now()->toDateString();

        // Totals for window
        $expQ = Expense::query()->when($userId, fn($q) => $q->where('user_id', $userId))->whereBetween('date', [$from, $to]);
        $incQ = Income::query()->when($userId, fn($q) => $q->where('user_id', $userId))->whereBetween('date', [$from, $to]);

        $totals = [
            'expenses' => (float)$expQ->sum('amount'),
            'incomes'  => (float)$incQ->sum('amount'),
        ];

        // Spend by category (top 8)
        $byCategory = Expense::query()
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->whereBetween('date', [$from, $to])
            ->selectRaw("COALESCE(NULLIF(category, ''), 'Uncategorized') AS category, SUM(amount) AS total")
            ->groupBy('category')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn($r) => ['category' => (string)$r->category, 'total' => (float)$r->total])
            ->values()
            ->all();

        // Overspend anomalies via your service
        $overspend = $insights->overspendByCategory($userId, 'range', $from, $to);

        // Budgets for month of "from"
        $monthStart = Carbon::parse($from)->startOfMonth();
        $monthEnd   = (clone $monthStart)->endOfMonth();

        $budgets = Budget::query()
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->whereDate('month', $monthStart->toDateString())
            ->get();

        $spendByCatForMonth = Expense::query()
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->selectRaw("COALESCE(NULLIF(category, ''), 'Uncategorized') AS category, SUM(amount) AS spend")
            ->groupBy('category')
            ->pluck('spend', 'category');

        $budgetCards = $budgets->map(function($b) use ($spendByCatForMonth, $monthStart) {
            $spent = (float)($spendByCatForMonth[$b->category] ?? 0);
            $limit = (float)$b->amount_decimal;
            $usage = $limit > 0 ? round(($spent / $limit) * 100, 1) : 0.0;
            return [
                'category' => $b->category,
                'month' => $monthStart->toDateString(),
                'limit' => $limit,
                'spent' => round($spent, 2),
                'usage_pct' => $usage,
            ];
        })->values()->all();

        $context = [
            'window' => ['from' => $from, 'to' => $to],
            'totals' => $totals,
            'by_category' => $byCategory,
            'overspend' => $overspend,
            'budgets' => $budgetCards,
        ];

        $items = $advice->generate($context);

        return response()->json([
            'source' => 'ai',
            'items' => $items['items'] ?? [],
        ]);
    }
}
