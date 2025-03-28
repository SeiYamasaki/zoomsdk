<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BookingApiController;
use App\Http\Controllers\MeetingParticipantController;
use App\Http\Controllers\ZoomSignatureController;
use App\Http\Controllers\MeetingController;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/calendar', [BookingController::class, 'index'])->name('calendar');
Route::get('/bookings', [BookingApiController::class, 'index']);
// Route::post('/bookings', [BookingApiController::class, 'store']);
Route::delete('/bookings/{id}', [BookingApiController::class, 'destroy']);

// 予約一覧取得のためのAPIルート
Route::get('/api/bookings', [BookingApiController::class, 'list']);

// 予約一覧表示用のルート
Route::get('/bookings/list', [BookingController::class, 'list'])->name('bookings.list');

// ミーティング参加登録用のルート
Route::get('/meetings/{id}/register', [MeetingParticipantController::class, 'showRegistrationForm'])->name('meetings.register');
Route::post('/meetings/{id}/register', [MeetingParticipantController::class, 'register'])->name('meetings.register.post');
Route::get('/meetings/complete/{token}', [MeetingParticipantController::class, 'showRegistrationComplete'])->name('meetings.registration.complete');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // 🔹 予約削除ルート（管理画面から使用）
    Route::delete('/bookings/{id}', [BookingApiController::class, 'destroy'])->name('bookings.destroy');
});
// JWT 署名を生成
Route::get('/api/zoom-signature', [ZoomSignatureController::class, 'generate']);
Route::post('/zoom-signature', [ZoomSignatureController::class, 'generate']);

// Meeting SDKルート
// 例：routes/web.php
Route::get('/meetings/join/{booking}', function (App\Models\Booking $booking) {
    return view('meetings.join', [
        'meetingId' => $booking->zoom_meeting_id,
        'meetingPassword' => $booking->zoom_meeting_password,
    ]);
})->name('meetings.join');


Route::get('/debug-zoom-key', function () {
    dd(config('services.zoom.sdk_key'));
});


Route::get('/debug-zoom-secret', function () {
    return response()->json([
        'sdk_key' => config('services.zoom.sdk_key'),
        'sdk_secret' => config('services.zoom.sdk_secret')
    ]);
});


require __DIR__ . '/auth.php';
