<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DebtController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1/auth')->name('api.v1.auth.')->group(function (): void {
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:auth-register')->name('register');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:auth-login')->name('login');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('me', [AuthController::class, 'me'])->name('me');
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    });
});

Route::prefix('v1')->name('api.v1.')->middleware('auth:sanctum')->group(function (): void {
    Route::get('debts', [DebtController::class, 'index'])->name('debts.index');
    Route::post('debts', [DebtController::class, 'store'])->name('debts.store');
    Route::get('debts/{debt}', [DebtController::class, 'show'])->name('debts.show');
    Route::put('debts/{debt}', [DebtController::class, 'update'])->name('debts.update');
    Route::delete('debts/{debt}', [DebtController::class, 'destroy'])->name('debts.destroy');
});
