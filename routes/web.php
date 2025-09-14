<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// User web routes
Route::resource('users', UserController::class)->except(['store', 'update', 'destroy']);
Route::get('/users/search', [UserController::class, 'search'])->name('users.search');
Route::get('/users/active', [UserController::class, 'active'])->name('users.active');
