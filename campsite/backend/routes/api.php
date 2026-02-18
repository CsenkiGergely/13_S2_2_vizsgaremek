<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CampingController;
use App\Http\Controllers\BookingSearchController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CampingPhotoController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CampingSpotController;

use App\Http\Controllers\SearchController;

Route::get('/search', [SearchController::class, 'search']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('posts', PostController::class);
Route::post('/posts', [PostController::class, 'index']);

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
Route::get('/bookings/getAll', [BookingController::class, 'getAllBookings']);

// Értékelések (publikus lekérés)
Route::get('/campings/{campingId}/comments', [CommentController::class, 'index']);

// Kemping képek (publikus lekérés)
Route::get('/campings/{campingId}/photos', [CampingPhotoController::class, 'index']);

// Kemping helyek (publikus lekérés)
Route::get('/campings/{campingId}/spots', [CampingSpotController::class, 'index']);
Route::get('/campings/{campingId}/spots/{spotId}', [CampingSpotController::class, 'show']);

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
    
    // Kemping helyek kezelése (csak tulajdonosoknak)
    Route::post('/campings/{campingId}/spots', [CampingSpotController::class, 'store']);
    Route::put('/campings/{campingId}/spots/{spotId}', [CampingSpotController::class, 'update']);
    Route::delete('/campings/{campingId}/spots/{spotId}', [CampingSpotController::class, 'destroy']);
    
    // Kemping képek kezelése (csak tulajdonosoknak)
    Route::post('/campings/{campingId}/photos', [CampingPhotoController::class, 'upload']);
    Route::delete('/campings/{campingId}/photos/{photoId}', [CampingPhotoController::class, 'destroy']);
    
    // Értékelések kezelése (authentikált felhasználóknak)
    Route::post('/campings/{campingId}/comments', [CommentController::class, 'store']); // Új értékelés
    Route::post('/comments/{commentId}/reply', [CommentController::class, 'reply']); // Válasz (csak tulajdonos)
    Route::put('/comments/{commentId}', [CommentController::class, 'update']); // Saját szerkesztése
    Route::delete('/comments/{commentId}', [CommentController::class, 'destroy']); // Saját törlése
});

