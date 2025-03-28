<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingApiController;
use App\Http\Controllers\ZoomSignatureController;

Route::get('/bookings', [BookingApiController::class, 'index']);
Route::post('/bookings', [BookingApiController::class, 'store']);
Route::delete('/bookings/{id}', [BookingApiController::class, 'destroy']); // 👈 削除用ルート
Route::post('/zoom-signature', [ZoomSignatureController::class, 'generate']);

