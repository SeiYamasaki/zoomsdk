<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class BookingApiController extends Controller
{
    public function index()
    {
        $bookings = Booking::all()->map(function ($booking) {
            return [
                'id' => $booking->id,
                'title' => $booking->title,
                'start' => $booking->start ? $booking->start->toIso8601String() : null,
                'end' => $booking->end ? $booking->end->toIso8601String() : null,
            ];
        });

        return response()->json($bookings, 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function store(Request $request)
    {
        try {
            Log::info('予約リクエスト受信:', $request->all()); // デバッグ用ログ

            // リクエストデータのバリデーション
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'start' => 'required|date_format:Y-m-d\TH:i:s',
                'end' => 'required|date_format:Y-m-d\TH:i:s|after:start',
            ]);

            // データの保存
            $booking = Booking::create($validated);

            Log::info('予約成功:', ['id' => $booking->id]);

            return response()->json($booking, 201);
        } catch (\Exception $e) {
            Log::error('予約エラー:', ['message' => $e->getMessage()]);
            return response()->json(['error' => '予約の登録に失敗しました'], 500);
        }
    }
}
