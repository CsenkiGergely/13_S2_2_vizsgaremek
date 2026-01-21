<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CampingController;
use App\Http\Controllers\BookingSearchController;
use App\Http\Controllers\CampingPhotoController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('posts', PostController::class);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::get('/campings', [CampingController::class, 'getCampings']);
Route::get('/booking/search', [BookingSearchController::class, 'search']);

// Camping képek kezelése
Route::get('/campings/{campingId}/photos', [CampingPhotoController::class, 'index']);
Route::post('/campings/{campingId}/photos', [CampingPhotoController::class, 'upload']);
Route::post('/campings/{campingId}/photos/url', [CampingPhotoController::class, 'addByUrl']);
Route::delete('/campings/{campingId}/photos/{photoId}', [CampingPhotoController::class, 'destroy']);

