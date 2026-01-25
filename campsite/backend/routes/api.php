<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CampingController;
use App\Http\Controllers\BookingSearchController;
use App\Http\Controllers\BookingController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('posts', PostController::class);

// Auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Kempingek
Route::get('/campings', [CampingController::class, 'getCampings']);
Route::get('/booking/search', [BookingSearchController::class, 'search']);

// Foglalások
Route::middleware('auth:sanctum')->group(function () {
    // felhasználói foglalások
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::put('/bookings/{id}', [BookingController::class, 'update']);
    Route::delete('/bookings/{id}', [BookingController::class, 'destroy']);
    Route::get('/bookings/{id}/qr-code', [BookingController::class, 'getQrCode']);
    
    // tulajdonosi funkciók
    Route::get('/owner/bookings', [BookingController::class, 'ownerBookings']);
    Route::patch('/bookings/{id}/status', [BookingController::class, 'updateStatus']);
    Route::post('/bookings/scan', [BookingController::class, 'scanQrCode']);
});

