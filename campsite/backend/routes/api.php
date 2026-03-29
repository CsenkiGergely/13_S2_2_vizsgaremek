<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CampingController;
use App\Http\Controllers\BookingSearchController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CampingPhotoController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CampingSpotController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\UserGuestController;
use App\Http\Controllers\CampingTagController;
use App\Http\Controllers\EntranceGateController;
use App\Http\Controllers\DashboardController;

Route::get('/search', [SearchController::class, 'search']);
Route::get('/search/tags', [SearchController::class, 'tags']);
Route::get('/search/prices', [SearchController::class, 'prices']);
Route::get('/locations/suggest', [SearchController::class, 'suggest']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Auth
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:3,15');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');
Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
Route::post('/resend-verification', [AuthController::class, 'resendVerification']);

// Partner státuszra váltás only login  -> nincs külön nincs jogosultságod üzenet
Route::post('/upgrade-to-partner', [AuthController::class, 'upgradeToPartner'])->middleware('auth:sanctum');

// Kempingek (publikus)
Route::get('/campings/top', [CampingController::class, 'getTopCampings']);
Route::get('/campings/stats', [CampingController::class, 'getStats']);
Route::get('/campings/{id}', [CampingController::class, 'show']);
Route::get('/campings/{id}/availability', [CampingController::class, 'getAvailability']);
Route::get('/booking/search', [BookingSearchController::class, 'search']);

// Értékelések (publikus lekérés)
Route::get('/campings/{campingId}/comments', [CommentController::class, 'index']);

// Kemping képek (publikus lekérés)
Route::get('/campings/{campingId}/photos', [CampingPhotoController::class, 'index']);

// Kemping helyek (publikus lekérés)
Route::get('/campings/{campingId}/spots', [CampingSpotController::class, 'index']);
Route::get('/campings/{campingId}/spots/{spotId}', [CampingSpotController::class, 'show']);

// ESP32 QR szkenner végpont (saját Bearer auth_token, nem Sanctum)
// Regisztrálva a Sanctum csoport ELŐTT, hogy a POST /bookings/{id} wildcard ne kapja el
Route::post('/bookings/scan-image', [BookingController::class, 'scanImage']);
// GeoJSON térkép lekérése (publikus - nem kell auth)
Route::get('/campings/{id}/geojson', [CampingController::class, 'getGeojson']);

// Kemping tag-ek (publikus lekérés)
Route::get('/campings/{campingId}/tags', [CampingTagController::class, 'index']);

// Foglalások
Route::middleware('auth:sanctum')->group(function () {
    // Összes kemping lekérése (csak superuser)
    Route::get('/campings', [CampingController::class, 'getCampings']);

    // felhasználói foglalások
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/getAll', [BookingController::class, 'getAllBookings']);
    Route::get('/bookings/prices', [BookingController::class, 'getPrices']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::put('/bookings/{id}', [BookingController::class, 'update']);
    Route::delete('/bookings/{id}', [BookingController::class, 'destroy']);
    Route::get('/bookings/{id}/qr-code', [BookingController::class, 'getQrCode']);
    
    // tulajdonosi funkciók
    Route::get('/owner/bookings', [BookingController::class, 'ownerBookings']);
    Route::patch('/bookings/{id}/status', [BookingController::class, 'updateStatus']);
    Route::post('/bookings/scan', [BookingController::class, 'scanQrCode']);
    
    // Saját kempingek lekérése (tulajdonos)
    Route::get('/my-campings', [CampingController::class, 'myCampings']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'getOwnerDashboard']);
    
    // Kemping kezelés (csak tulajdonosoknak)
    Route::post('/campings', [CampingController::class, 'store'])->middleware('throttle:5,1');
    Route::put('/campings/{id}', [CampingController::class, 'update']);
    Route::delete('/campings/{id}', [CampingController::class, 'destroy']);
    
    // GeoJSON térkép kezelése (csak tulajdonos)
    Route::post('/campings/{id}/geojson', [CampingController::class, 'uploadGeojson']);
    Route::delete('/campings/{id}/geojson', [CampingController::class, 'deleteGeojson']);

    // Kapu kezelés (csak tulajdonos)
    Route::get('/campings/{campingId}/gates', [EntranceGateController::class, 'index']);
    Route::post('/campings/{campingId}/gates', [EntranceGateController::class, 'store']);
    Route::put('/campings/{campingId}/gates/{gateId}', [EntranceGateController::class, 'update']);
    Route::delete('/campings/{campingId}/gates/{gateId}', [EntranceGateController::class, 'destroy']);

    // Kapu szintű auth token (csak tulajdonos)
    Route::post('/campings/{campingId}/gates/{gateId}/auth-token', [EntranceGateController::class, 'generateToken']);
    Route::get('/campings/{campingId}/gates/{gateId}/auth-token', [EntranceGateController::class, 'getTokenStatus']);
    Route::delete('/campings/{campingId}/gates/{gateId}/auth-token', [EntranceGateController::class, 'revokeToken']);
    
    // Kemping helyek kezelése (csak tulajdonosoknak)
    Route::post('/campings/{campingId}/spots', [CampingSpotController::class, 'store'])->middleware('throttle:20,1');
    Route::put('/campings/{campingId}/spots/{spotId}', [CampingSpotController::class, 'update']);
    Route::delete('/campings/{campingId}/spots/{spotId}', [CampingSpotController::class, 'destroy']);
    
    // Kemping képek kezelése (csak tulajdonosoknak)
    Route::post('/campings/{campingId}/photos', [CampingPhotoController::class, 'upload']);
    Route::post('/campings/{campingId}/photos/url', [CampingPhotoController::class, 'addByUrl']);
    Route::put('/campings/{campingId}/photos/{photoId}/set-main', [CampingPhotoController::class, 'setMain']);
    Route::delete('/campings/{campingId}/photos/{photoId}', [CampingPhotoController::class, 'destroy']);
    
    // Értékelések kezelése (authentikált felhasználóknak)
    Route::get('/owner/comments', [CommentController::class, 'ownerComments']); // Tulajdonos összes értékelése
    Route::post('/campings/{campingId}/comments', [CommentController::class, 'store']); // Új értékelés
    Route::post('/comments/{commentId}/reply', [CommentController::class, 'reply']); // Válasz (csak tulajdonos)
    Route::put('/comments/{commentId}', [CommentController::class, 'update']); // Saját szerkesztése
    Route::delete('/comments/{commentId}', [CommentController::class, 'destroy']); // Saját törlése

    // Vendég adatok kezelése (bejelentkezett felhasználóknak)
    Route::get('/user-guests', [UserGuestController::class, 'index']);
    Route::post('/user-guests', [UserGuestController::class, 'store']);
    Route::get('/user-guests/{id}', [UserGuestController::class, 'show']);
    Route::put('/user-guests/{id}', [UserGuestController::class, 'update']);
    Route::delete('/user-guests/{id}', [UserGuestController::class, 'destroy']);

    // Kemping tag-ek kezelése (csak tulajdonosoknak)
    Route::put('/campings/{campingId}/tags/sync', [CampingTagController::class, 'sync']);

});