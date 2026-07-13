<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\BookingApiController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\DeliveryLogApiController;
use App\Http\Controllers\Api\DeliverySettingsApiController;
use App\Http\Controllers\Api\FacilityApiController;
use App\Http\Controllers\Api\GuestApiController;
use App\Http\Controllers\Api\OutletApiController;
use App\Http\Controllers\Api\PropertyApiController;
use App\Http\Controllers\Api\ReportApiController;
use App\Http\Controllers\Api\RoleApiController;
use App\Http\Controllers\Api\RoomApiController;
use App\Http\Controllers\Api\ScanHistoryApiController;
use App\Http\Controllers\Api\UserApiController;
use App\Http\Controllers\Api\VoucherApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function () {
    // Auth (no auth middleware)
    Route::post('/login', [AuthApiController::class, 'login'])->middleware('throttle:5,1');

    // Public voucher access (no auth middleware)
    Route::get('/vouchers/public/{token}', [VoucherApiController::class, 'publicShow'])
        ->middleware('throttle:30,1');

    // Authenticated routes
    Route::middleware('auth')->group(function () {
        // Auth
        Route::post('/logout', [AuthApiController::class, 'logout']);
        Route::get('/me', [AuthApiController::class, 'me']);

        // Dashboard
        Route::get('/dashboard', [DashboardApiController::class, 'index']);

        // Properties
        Route::apiResource('properties', PropertyApiController::class);

        // Rooms
        Route::apiResource('rooms', RoomApiController::class);

        // Guests
        Route::apiResource('guests', GuestApiController::class);

        // Bookings
        Route::get('/bookings', [BookingApiController::class, 'index']);
        Route::get('/bookings/create-data', [BookingApiController::class, 'formData']);
        Route::post('/bookings', [BookingApiController::class, 'store']);
        Route::get('/bookings/{booking}', [BookingApiController::class, 'show']);
        Route::put('/bookings/{booking}', [BookingApiController::class, 'update']);
        Route::delete('/bookings/{booking}', [BookingApiController::class, 'destroy']);
        Route::post('/bookings/{booking}/check-in', [BookingApiController::class, 'checkIn']);
        Route::post('/bookings/{booking}/check-out', [BookingApiController::class, 'checkOut']);

        // Vouchers
        Route::get('/vouchers', [VoucherApiController::class, 'index']);
        Route::get('/vouchers/create-data', [VoucherApiController::class, 'formData']);
        Route::post('/vouchers/generate', [VoucherApiController::class, 'generate']);
        Route::get('/vouchers/{voucher}', [VoucherApiController::class, 'show']);
        Route::post('/vouchers/{voucher}/update', [VoucherApiController::class, 'update']);
        Route::post('/vouchers/verify', [VoucherApiController::class, 'verify'])->middleware('throttle:30,1');
        Route::post('/vouchers/process', [VoucherApiController::class, 'process'])->middleware('throttle:voucher-redeem');
        Route::post('/vouchers/redeem', [VoucherApiController::class, 'process'])->middleware('throttle:voucher-redeem');

        // Facilities
        Route::apiResource('facilities', FacilityApiController::class);

        // Outlets
        Route::apiResource('outlets', OutletApiController::class);

        // Users
        Route::apiResource('users', UserApiController::class);

        // Roles
        Route::get('/roles/permissions', [RoleApiController::class, 'permissions']);
        Route::apiResource('roles', RoleApiController::class);

        // Reports
        Route::get('/reports', [ReportApiController::class, 'index']);
        Route::get('/reports/form-data', [ReportApiController::class, 'formData']);

        // Delivery Logs
        Route::get('/delivery-logs', [DeliveryLogApiController::class, 'index']);

        // Scan History
        Route::get('/scan-history', [ScanHistoryApiController::class, 'index']);

        // Delivery Settings
        Route::get('/settings/delivery', [DeliverySettingsApiController::class, 'index']);
        Route::post('/settings/delivery', [DeliverySettingsApiController::class, 'update']);
        Route::post('/settings/delivery/toggle-whatsapp', [DeliverySettingsApiController::class, 'toggleWhatsApp']);
    });
});
