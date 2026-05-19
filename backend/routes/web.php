<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OutlookOAuthController;

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

// OAuth routes (need session middleware for CSRF state)
Route::get('/oauth/authorize', [OutlookOAuthController::class, 'authorize']);
Route::get('/oauth/callback', [OutlookOAuthController::class, 'callback']);
