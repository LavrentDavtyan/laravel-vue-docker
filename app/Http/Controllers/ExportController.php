<?php

namespace App\Http\Controllers;

use App\Exports\IncomesExport;
use App\Exports\ExpensesExport;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    public function exportExpenses()
    {
        return Excel::download(
            new ExpensesExport(),
            'expenses_' . now()->toDateString() . '.xlsx'
        );
    }

    public function exportIncomes()
    {
        return Excel::download(
            new IncomesExport(),
            'incomes_' . now()->toDateString() . '.xlsx'
        );
    }


}
