<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\BookingApiController;
use App\Http\Controllers\Api\VoucherApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function () {
    Route::post('/login', [AuthApiController::class, 'login'])->middleware('throttle:5,1');

    Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthApiController::class, 'logout']);

    Route::get('/bookings', [BookingApiController::class, 'index']);

    Route::post('/vouchers/generate', [VoucherApiController::class, 'generate']);
    Route::post('/vouchers/redeem', [VoucherApiController::class, 'redeem'])
        ->middleware('throttle:voucher-redeem');
    });
});
