<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

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


Route::prefix('api')
    ->middleware(['web', 'tenant']) // <= IMPORTANT: web + tenant
    ->group(function () {
        Route::post('/login',  [AuthController::class, 'login']);
        Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');
        Route::get('/me',      [AuthController::class, 'me'])->middleware('auth');
        Route::post('/auth/heartbeat', [AuthController::class, 'heartbeat'])->middleware('auth');
    });

/*
|--------------------------------------------------------------------------
| Your SPA shell + fallback
|--------------------------------------------------------------------------
*/
Route::view('/', 'welcome');

Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '^(?!api|sanctum|storage|broadcasting|horizon|telescope).*$');