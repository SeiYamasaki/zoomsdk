<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BookingApiController extends Controller
{
    public function index()
    {
        return response()->json(Booking::all(), 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function destroy($id)
    {
        try {
            Log::info("予約削除リクエスト受信: ID=$id");

            $booking = Booking::findOrFail($id); // 🔹 findOrFail を使用

            $booking->delete();

            Log::info("予約削除成功: ID=$id");

            return response()->json([
                'message' => '予約が削除されました'
            ], 200)->header('Content-Type', 'application/json'); // 🔹 return を追加
        } catch (\Exception $e) {
            Log::error("予約削除エラー: " . $e->getMessage());
            return response()->json([
                'error' => '予約の削除に失敗しました',
                'details' => $e->getMessage()
            ], 500)->header('Content-Type', 'application/json');
        }
    }

    public function store(Request $request)
    {
        try {
            Log::info('予約リクエスト受信:', $request->all());

            // 🔹 **バリデーション**
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'start' => 'required|date_format:Y-m-d\TH:i:s',
                'end' => 'required|date_format:Y-m-d\TH:i:s|after:start',
            ]);

            // 🔹 **日本時間に変換**
            $validated['start'] = Carbon::parse($validated['start'])->setTimezone('Asia/Tokyo');
            $validated['end'] = Carbon::parse($validated['end'])->setTimezone('Asia/Tokyo');

            // 🔹 **データの保存**
            $booking = new Booking();
            $booking->fill($validated);
            $booking->save();

            Log::info('予約成功:', ['id' => $booking->id]);

            return response()->json($booking, 201)->header('Content-Type', 'application/json'); // 🔹 return を追加
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('バリデーションエラー:', ['message' => $e->getMessage()]);
            return response()->json([
                'error' => 'バリデーションエラー',
                'details' => $e->errors()
            ], 422)->header('Content-Type', 'application/json');
        } catch (\Exception $e) {
            Log::error('予約エラー:', ['message' => $e->getMessage()]);
            return response()->json([
                'error' => '予約の登録に失敗しました',
                'details' => $e->getMessage()
            ], 500)->header('Content-Type', 'application/json');
        }
    }
}
