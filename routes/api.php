<?php

use App\Http\Controllers\IncomeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\CategoryReportController;
use App\Http\Controllers\BudgetController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);
Route::get('/health', fn () => response()->json(['status' => 'ok']));

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);
    Route::post('/refresh',[AuthController::class, 'refresh']);

    // Export to Excel
    Route::get('/exports/expenses', [ExportController::class, 'exportExpenses']);
    Route::get('/exports/incomes', [ExportController::class, 'exportIncomes']);

    // Expenses
    Route::apiResource('expenses', ExpenseController::class);

    //Incomes
    Route::apiResource('incomes', IncomeController::class);

    Route::get('/expenses/category/{slug}', [CategoryReportController::class, 'showExpenses']);
    Route::get('/incomes/category/{slug}', [CategoryReportController::class, 'showIncomes']);

    Route::get('/budgets/stats', [BudgetController::class, 'stats']);
    Route::apiResource('budgets', BudgetController::class);

});
