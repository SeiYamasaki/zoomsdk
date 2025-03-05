<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingApiController;

Route::get('/bookings', [BookingApiController::class, 'index']);
