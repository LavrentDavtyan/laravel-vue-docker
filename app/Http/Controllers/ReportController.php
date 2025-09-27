<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Expense;
use Carbon\Carbon;

class ReportController extends Controller
{
    // GET /api/reports/category-share?start=YYYY-MM-DD&end=YYYY-MM-DD
    public function categoryShare(Request $request)
    {
        [$start, $end] = $this->range($request);


        $rows = Expense::select('category', DB::raw('SUM(amount) as total'))
            ->whereBetween('date', [$start, $end])
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $grandTotal = (float) $rows->sum('total');

        $data = $rows->map(function ($r) use ($grandTotal) {
            $share = $grandTotal > 0 ? round(($r->total / $grandTotal) * 100, 2) : 0.0;
            return [
                'category' => $r->category,
                'total'    => (float) $r->total,
                'share'    => $share, // percent
            ];
        });

        return response()->json([
            'range'       => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
            'grand_total' => $grandTotal,
            'categories'  => $data,
        ]);
    }

    public function summary(Request $request)
    {
        [$start, $end] = $this->range($request);

        $base = Expense::whereBetween('date', [$start, $end]);

        $count = (int) $base->count();
        $sum   = (float) $base->sum('amount');

        $days = max(1, $start->diffInDays($end) + 1);
        $avgPerTx  = $count > 0 ? round($sum / $count, 2) : 0.0;
        $avgPerDay = round($sum / $days, 2);

        return response()->json([
            'range'        => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
            'total'        => $sum,
            'count'        => $count,
            'days'         => $days,
            'avg_per_tx'   => $avgPerTx,
            'avg_per_day'  => $avgPerDay,
        ]);
    }


    private function range(Request $request): array
    {
        $start = $request->query('start');
        $end   = $request->query('end');

        if (!$start || !$end) {
            // default: current calendar month
            $s = Carbon::now()->startOfMonth();
            $e = Carbon::now()->endOfMonth();
        } else {
            $s = Carbon::parse($start)->startOfDay();
            $e = Carbon::parse($end)->endOfDay();
        }

        if ($e->lessThan($s)) {
            [$s, $e] = [$e, $s];
        }

        return [$s, $e];
    }
}
