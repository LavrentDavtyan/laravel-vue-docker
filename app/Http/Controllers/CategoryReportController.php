<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Expense;
use App\Models\Income;
use Carbon\Carbon;

class CategoryReportController extends Controller
{
    // Your existing showExpenses(Request $request, $slug) is fine.

    public function showIncomes(Request $request, $slug)
    {
        $userId = $request->user()->id;
        $from = $request->query('date_from', Carbon::now()->subDays(90)->toDateString());
        $to   = $request->query('date_to', Carbon::now()->toDateString());

        // Trend line (last 90 days)
        $trend = Income::where('user_id', $userId)
            ->where('category', $slug)
            ->whereBetween('date', [$from, $to])
            ->selectRaw('date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top descriptions (within same range)
        $top = Income::where('user_id', $userId)
            ->where('category', $slug)
            ->whereBetween('date', [$from, $to])
            ->selectRaw('description, SUM(amount) as total')
            ->groupBy('description')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Items (within same range)
        $items = Income::where('user_id', $userId)
            ->where('category', $slug)
            ->whereBetween('date', [$from, $to])
            ->orderByDesc('date')
            ->paginate(20);

        return response()->json([
            'trend' => $trend,
            'top'   => $top,
            'items' => $items,
        ]);
    }
}
