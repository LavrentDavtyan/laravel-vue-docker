<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Expense;
use App\Exports\ExpensesExport;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    // GET /api/expenses/export?category=&date=
    public function exportExpenses(Request $request)
    {
        $q = Expense::query();

        if ($request->filled('category')) {
            $q->where('category', 'like', '%'.$request->query('category').'%');
        }
        if ($request->filled('date')) {
            // adjust 'date' if your column name differs
            $q->whereDate('date', $request->query('date'));
        }

        $rows = $q->orderByDesc('date')->get();
        $filename = 'expenses_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(new ExpensesExport($rows), $filename);
    }
}
