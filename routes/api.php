<?php

use App\Http\Controllers\IncomeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\CategoryReportController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\HelperController;
use App\Http\Controllers\Share\ShareTopicController;
use App\Http\Controllers\Share\ShareExpenseController;

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





// Share Expenses


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

    Route::post('/helper/advice', [HelperController::class, 'advice']);

    // share expenses
    Route::prefix('share')->group(function () {
           // Topics
           Route::get('/topics', [ShareTopicController::class, 'index']);
           Route::post('/topics', [ShareTopicController::class, 'store']);

           // Members / invite / join / leave
           Route::get('/topics/{topic}/members', [ShareTopicController::class, 'members']);
           Route::post('/topics/{topic}/invite/rotate', [ShareTopicController::class, 'rotateInvite']);
           Route::post('/join/{token}', [ShareTopicController::class, 'joinByToken']);
           Route::post('/topics/{topic}/leave', [ShareTopicController::class, 'leave']);

           // Status
           Route::post('/topics/{topic}/close', [ShareTopicController::class, 'close']);
           Route::post('/topics/{topic}/open',  [ShareTopicController::class, 'open']);

           // Expenses
           Route::get('/topics/{topic}/expenses',  [ShareExpenseController::class, 'index']);
           Route::post('/topics/{topic}/expenses', [ShareExpenseController::class, 'store']);
           Route::delete('/topics/{topic}/expenses/{expense}', [ShareExpenseController::class, 'destroy']);

           // Balances
           Route::get('/topics/{topic}/balances', [ShareExpenseController::class, 'balances']);
       });
});
