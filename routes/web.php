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
use App\Http\Controllers\OutletController;
use App\Http\Controllers\DeliverySettingsController;
use App\Http\Controllers\DeliveryLogController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\ImportLogController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::get('v/{token}', [VoucherController::class, 'publicShow'])
    ->middleware('throttle:30,1')
    ->name('vouchers.public');
Route::get('v/{token}/qr.svg', [VoucherController::class, 'qrImagePublic'])
    ->middleware('throttle:30,1')
    ->name('vouchers.public.qr');

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('docs', DocsController::class)->name('docs');

    Route::resource('properties', PropertyController::class)->except(['destroy']);
    Route::delete('properties/{property}', [PropertyController::class, 'destroy'])->name('properties.destroy');

    Route::resource('rooms', RoomController::class)->except(['destroy']);
    Route::delete('rooms/{room}', [RoomController::class, 'destroy'])->name('rooms.destroy');
    Route::get('rooms-import', [RoomController::class, 'import'])->name('rooms.import');
    Route::post('rooms-import/process', [RoomController::class, 'processImport'])->name('rooms.process-import')->middleware(['validate.upload', 'throttle:10,1']);
    Route::get('rooms-import/template', [RoomController::class, 'downloadTemplate'])->name('rooms.download-template');

    Route::resource('guests', GuestController::class)->except(['destroy']);
    Route::delete('guests/{guest}', [GuestController::class, 'destroy'])->name('guests.destroy');
    Route::get('guests-import', [GuestController::class, 'import'])->name('guests.import');
    Route::post('guests-import/process', [GuestController::class, 'processImport'])->name('guests.process-import')->middleware(['validate.upload', 'throttle:10,1']);
    Route::get('guests-import/template', [GuestController::class, 'downloadTemplate'])->name('guests.download-template');

    Route::resource('bookings', BookingController::class)->except(['destroy']);
    Route::delete('bookings/{booking}', [BookingController::class, 'destroy'])->name('bookings.destroy');
    Route::post('bookings/{booking}/check-in', [BookingController::class, 'checkIn'])->name('bookings.check-in');
    Route::post('bookings/{booking}/check-out', [BookingController::class, 'checkOut'])->name('bookings.check-out');
    Route::get('bookings-import', [BookingController::class, 'import'])->name('bookings.import');
    Route::post('bookings-import/process', [BookingController::class, 'processImport'])->name('bookings.process-import')->middleware(['validate.upload', 'throttle:10,1']);
    Route::get('bookings-import/template', [BookingController::class, 'downloadTemplate'])->name('bookings.download-template');

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
    Route::post('vouchers/scan-verify', [VoucherController::class, 'verifyScannedCode'])
        ->middleware('throttle:30,1')  // SECURITY FIX: Added rate limiting (30 req/min)
        ->name('vouchers.scan.verify');
    Route::get('vouchers/{voucher}/edit', [VoucherController::class, 'edit'])->name('vouchers.edit');
    Route::get('vouchers/{voucher}', [VoucherController::class, 'show'])->name('vouchers.show');
    Route::post('vouchers/{voucher}/update', [VoucherController::class, 'update'])->name('vouchers.update');
    Route::get('vouchers/{voucher}/qr.svg', [VoucherController::class, 'qrImage'])->name('vouchers.qr');

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/redemptions/export', [ReportController::class, 'exportRedemptions'])->name('reports.redemptions.export')->middleware('throttle:20,1');
    Route::get('reports/delivery-logs', [DeliveryLogController::class, 'index'])->name('reports.delivery-logs');
    Route::get('reports/delivery-logs/export', [DeliveryLogController::class, 'export'])->name('reports.delivery-logs.export')->middleware('throttle:20,1');
    Route::get('reports/scan-history', [\App\Http\Controllers\QrScanLogController::class, 'index'])->name('reports.scan-history');
    Route::get('reports/scan-history/export', [\App\Http\Controllers\QrScanLogController::class, 'export'])->name('reports.scan-history.export')->middleware('throttle:20,1');

    Route::get('import-logs', [ImportLogController::class, 'index'])->name('import-logs.index');
    Route::get('import-logs/{importLog}', [ImportLogController::class, 'show'])->name('import-logs.show');

    Route::get('settings/delivery', [DeliverySettingsController::class, 'index'])->name('settings.delivery');
    Route::post('settings/delivery', [DeliverySettingsController::class, 'update'])->name('settings.delivery.update');
    Route::post('settings/delivery/toggle-whatsapp', [DeliverySettingsController::class, 'toggleWhatsApp'])->name('settings.delivery.toggle-whatsapp');

    Route::post('bookings/{booking}/resend', [VoucherController::class, 'resend'])->name('bookings.resend');

    Route::resource('users', UserController::class);
    Route::resource('roles', RoleController::class);
    Route::resource('facilities', FacilityController::class);
    Route::resource('outlets', OutletController::class)->except(['show']);
});
