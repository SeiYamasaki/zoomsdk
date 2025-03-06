<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BookingApiController extends Controller
{
    public function index()
    {
        return response()->json(Booking::all(), 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function store(Request $request) // 👈 **このメソッドが存在しないとエラーになる**
    {
        try {
            Log::info('予約リクエスト受信:', $request->all());

            // **バリデーション**
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'start' => 'required|date_format:Y-m-d\TH:i:s',
                'end' => 'required|date_format:Y-m-d\TH:i:s|after:start',
            ]);

            // **日本時間に変換**
            $validated['start'] = Carbon::parse($validated['start'])->setTimezone('Asia/Tokyo');
            $validated['end'] = Carbon::parse($validated['end'])->setTimezone('Asia/Tokyo');

            // **データの保存**
            $booking = Booking::create($validated);

            Log::info('予約成功:', ['id' => $booking->id]);

            return response()->json($booking, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('バリデーションエラー:', ['message' => $e->getMessage()]);
            return response()->json([
                'error' => 'バリデーションエラー',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('予約エラー:', ['message' => $e->getMessage()]);
            return response()->json([
                'error' => '予約の登録に失敗しました',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
