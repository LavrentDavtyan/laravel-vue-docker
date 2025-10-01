<?php

namespace App\Services;

use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class InsightService
{
    /**
     * Overspend by category for a given period.
     * period: 'week' (default) — compares current week vs rolling 4-week average (excluding current week)
     * If date_from/date_to are provided, we use that range as the "current period" and compare vs previous 4 same-length windows.
     *
     * @return array<int, array{
     *   category: string,
     *   delta_pct: float,
     *   current: float,
     *   baseline: float,
     *   message: string,
     *   window: array{from:string,to:string}
     * }>
     */
    public function overspendByCategory(int $userId, string $period = 'week', ?string $dateFrom = null, ?string $dateTo = null): array
    {
        // Resolve current window
        if ($dateFrom && $dateTo) {
            $start = Carbon::parse($dateFrom)->startOfDay();
            $end   = Carbon::parse($dateTo)->endOfDay();
        } else {
            // Follow the app's prior "week" definition (Sunday → today)
            $today = Carbon::today();
            $start = (clone $today)->startOfWeek(Carbon::SUNDAY);
            $end   = (clone $today)->endOfDay();
            if ($period !== 'week') {
                // In case we extend later (month, etc). For now we stick to week.
                $period = 'week';
            }
        }

        $daysInWindow = max(1, $start->diffInDays($end) + 1);

        // Current window spend per category
        $current = Expense::query()
            ->where('user_id', $userId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('category, COALESCE(SUM(amount),0) as total')
            ->groupBy('category')
            ->pluck('total', 'category'); // ['Food'=>123.45, ...]

        // Build 4 previous windows of the same length, contiguous, BEFORE current window.
        // Example: if current is Sun..Sat (7 days), we take previous 4 weeks (7 days each) immediately prior.
        $baselines = collect();
        $cursorEnd = (clone $start)->subDay(); // day before current window
        for ($i = 0; $i < 4; $i++) {
            $winEnd   = (clone $cursorEnd)->endOfDay();
            $winStart = (clone $cursorEnd)->subDays($daysInWindow - 1)->startOfDay();

            $chunk = Expense::query()
                ->where('user_id', $userId)
                ->whereBetween('date', [$winStart->toDateString(), $winEnd->toDateString()])
                ->selectRaw('category, COALESCE(SUM(amount),0) as total')
                ->groupBy('category')
                ->pluck('total', 'category'); // map category=>sum for this window

            $baselines->push($chunk);
            // next previous window starts right before this one
            $cursorEnd = (clone $winStart)->subDay();
        }

        // Compute rolling average per category across the 4 windows
        $avg = $this->averageByKey($baselines);

        // Build result rows, only where current > 0 and baseline > 0, or current significantly > baseline
        $rows = [];
        foreach ($current as $cat => $cur) {
            $base = (float) ($avg[$cat] ?? 0.0);
            // If baseline tiny (0) and current small, skip; if current > 0 and baseline 0, delta is 100%+
            if ($cur <= 0) continue;

            $deltaPct = $base > 0 ? (($cur - $base) / $base) * 100.0 : 100.0;
            if ($deltaPct <= 0) continue; // only surface overspends (positive delta)

            $rows[] = [
                'category' => $cat,
                'delta_pct' => round($deltaPct, 1),
                'current' => round((float)$cur, 2),
                'baseline' => round($base, 2),
                'message' => sprintf(
                    'You spent %s%% more on %s than your 4-week average.',
                    number_format(round($deltaPct, 0)),
                    $cat ?: 'Uncategorized'
                ),
                'window' => [
                    'from' => $start->toDateString(),
                    'to'   => $end->toDateString(),
                ],
            ];
        }

        // Sort by largest overspend first
        usort($rows, fn($a, $b) => $b['delta_pct'] <=> $a['delta_pct']);

        return $rows;
    }

    /**
     * @param Collection<int, \Illuminate\Support\Collection<string,float>> $windows
     * @return array<string,float> average by key (category)
     */
    protected function averageByKey(Collection $windows): array
    {
        $totals = [];
        $counts = [];
        foreach ($windows as $map) {
            foreach ($map as $k => $v) {
                $totals[$k] = ($totals[$k] ?? 0) + (float)$v;
                $counts[$k] = ($counts[$k] ?? 0) + 1;
            }
        }
        $avg = [];
        foreach ($totals as $k => $sum) {
            $avg[$k] = $counts[$k] ? $sum / $counts[$k] : 0.0;
        }
        return $avg;
    }
}
