<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Exports\ExpensesExport;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    public function exportExpenses()
    {
        $userId = Auth::id();


        return Excel::download(
            new ExpensesExport($userId),
            'expenses_' . now()->toDateString() . '.xlsx'
        );
    }
}
