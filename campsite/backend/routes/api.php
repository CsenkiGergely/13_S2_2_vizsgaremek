<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CampingController;
use App\Http\Controllers\BookingSearchController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CampingPhotoController;


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

// Partner státuszra váltás only login  -> nincs külön nincs jogosultságod üzenet
Route::post('/upgrade-to-partner', [AuthController::class, 'upgradeToPartner'])->middleware('auth:sanctum');

// Kempingek
Route::get('/campings', [CampingController::class, 'getCampings']);
Route::get('/campings/{id}', [CampingController::class, 'show']);
Route::get('/campings/{id}/spots', [CampingController::class, 'getSpots']);
Route::get('/campings/{id}/availability', [CampingController::class, 'getAvailability']);
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
    
    // Kemping kezelés (csak tulajdonosoknak)
    Route::post('/campings', [CampingController::class, 'store']);
    Route::put('/campings/{id}', [CampingController::class, 'update']);
    Route::delete('/campings/{id}', [CampingController::class, 'destroy']);
});

