<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\FacilityController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::get('v/{token}', [VoucherController::class, 'publicShow'])
    ->name('vouchers.public');
Route::get('v/{token}/qr.svg', [VoucherController::class, 'qrImagePublic'])
    ->name('vouchers.public.qr');

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::resource('properties', PropertyController::class)->except(['destroy']);
    Route::delete('properties/{property}', [PropertyController::class, 'destroy'])->name('properties.destroy');

    Route::resource('rooms', RoomController::class)->except(['destroy']);
    Route::delete('rooms/{room}', [RoomController::class, 'destroy'])->name('rooms.destroy');

    Route::resource('guests', GuestController::class)->except(['destroy']);
    Route::delete('guests/{guest}', [GuestController::class, 'destroy'])->name('guests.destroy');

    Route::resource('bookings', BookingController::class)->except(['destroy']);
    Route::delete('bookings/{booking}', [BookingController::class, 'destroy'])->name('bookings.destroy');
    Route::post('bookings/{booking}/check-in', [BookingController::class, 'checkIn'])->name('bookings.check-in');

    Route::get('vouchers', [VoucherController::class, 'index'])->name('vouchers.index');
    Route::get('vouchers/redeem', [VoucherController::class, 'redeemForm'])->name('vouchers.redeem.form');
    Route::get('vouchers/scan', [VoucherController::class, 'scanForm'])->name('vouchers.scan.form');
    Route::post('vouchers/generate', [VoucherController::class, 'generate'])->name('vouchers.generate');
    Route::post('vouchers/redeem', [VoucherController::class, 'redeem'])
        ->middleware('throttle:voucher-redeem')
        ->name('vouchers.redeem');
    Route::post('vouchers/scan-process', [VoucherController::class, 'processScannedCode'])
        ->middleware('throttle:voucher-redeem')
        ->name('vouchers.scan.process');
    Route::get('vouchers/{voucher}', [VoucherController::class, 'show'])->name('vouchers.show');
    Route::get('vouchers/{voucher}/qr.svg', [VoucherController::class, 'qrImage'])->name('vouchers.qr');

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');

    Route::resource('users', UserController::class);
    Route::resource('roles', RoleController::class);
    Route::resource('facilities', FacilityController::class);
});
