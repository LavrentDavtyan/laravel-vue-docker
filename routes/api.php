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
use App\Http\Controllers\Share\ShareJoinRequestController;

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
    Route::get('/exports/incomes',  [ExportController::class, 'exportIncomes']);

    // Expenses
    Route::apiResource('expenses', ExpenseController::class);

    // Incomes
    Route::apiResource('incomes', IncomeController::class);

    Route::get('/expenses/category/{slug}', [CategoryReportController::class, 'showExpenses']);
    Route::get('/incomes/category/{slug}',  [CategoryReportController::class, 'showIncomes']);

    Route::get('/budgets/stats', [BudgetController::class, 'stats']);
    Route::apiResource('budgets', BudgetController::class);

    Route::post('/helper/advice', [HelperController::class, 'advice']);

    // -----------------------------
    // Share Expenses
    // -----------------------------
    Route::prefix('share')
        ->scopeBindings() // ensure {joinRequest} belongs to {topic}
        ->group(function () {

            // Topics
            Route::get('/topics',  [ShareTopicController::class, 'index']);
            Route::post('/topics', [ShareTopicController::class, 'store']);

            // Members / invite / join / leave
            Route::get('/topics/{topic}/members',        [ShareTopicController::class, 'members'])->whereNumber('topic');
            Route::post('/topics/{topic}/invite/rotate', [ShareTopicController::class, 'rotateInvite'])->whereNumber('topic');
            Route::post('/join/{token}',                 [ShareTopicController::class, 'joinByToken']); // token is string
            Route::post('/topics/{topic}/leave',         [ShareTopicController::class, 'leave'])->whereNumber('topic');

            // Status
            Route::post('/topics/{topic}/close', [ShareTopicController::class, 'close'])->whereNumber('topic');
            Route::post('/topics/{topic}/open',  [ShareTopicController::class, 'open'])->whereNumber('topic');

            // Expenses
            Route::get('/topics/{topic}/expenses',               [ShareExpenseController::class, 'index'])->whereNumber('topic');
            Route::post('/topics/{topic}/expenses',              [ShareExpenseController::class, 'store'])->whereNumber('topic');
            Route::delete('/topics/{topic}/expenses/{expense}',  [ShareExpenseController::class, 'destroy'])->whereNumber('topic')->whereNumber('expense');

            // Balances
            Route::get('/topics/{topic}/balances', [ShareExpenseController::class, 'balances'])->whereNumber('topic');

            // Join requests
            Route::get('/topics/{topic}/join-requests',                        [ShareJoinRequestController::class, 'index'])->whereNumber('topic');   // owner
            Route::post('/topics/{topic}/join-requests',                       [ShareJoinRequestController::class, 'store'])->whereNumber('topic');   // requester
            Route::post('/topics/{topic}/join-requests/{joinRequest}/approve', [ShareJoinRequestController::class, 'approve'])->whereNumber('topic')->whereNumber('joinRequest'); // owner
            Route::post('/topics/{topic}/join-requests/{joinRequest}/deny',    [ShareJoinRequestController::class, 'deny'])->whereNumber('topic')->whereNumber('joinRequest');    // owner
        });
});
