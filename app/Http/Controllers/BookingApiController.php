<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BookingApiController extends Controller
{
    /**
     * 予約一覧を取得 (カレンダー用)
     */
    public function index()
    {
        $bookings = Booking::all()->map(function ($booking) {
            return [
                'id' => $booking->id,
                'title' => Carbon::parse($booking->start)->format('H:i') . ' - ' . Carbon::parse($booking->end)->format('H:i') . ' 予約',
                'start' => $booking->start,
                'end' => $booking->end
            ];
        });

        return response()->json($bookings, 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * 予約を削除
     */
    public function destroy($id)
    {
        try {
            Log::info("予約削除リクエスト受信: ID=$id");

            // 🔹 **IDが数値かどうかチェック**
            if (!is_numeric($id)) {
                Log::error("無効な予約ID: $id");
                return response()->json([
                    'error' => '無効な予約IDです'
                ], 400);
            }

            // 🔹 **予約を検索**
            $booking = Booking::find($id); // **`findOrFail` ではなく `find` を使用**
            if (!$booking) {
                Log::warning("予約削除エラー (存在しないID): ID=$id");
                return response()->json([
                    'error' => '予約が見つかりません'
                ], 404);
            }

            $booking->delete();

            Log::info("予約削除成功: ID=$id");

            return response()->json([
                'message' => '予約が削除されました'
            ], 200);
        } catch (\Exception $e) {
            Log::error("予約削除エラー: " . $e->getMessage());
            return response()->json([
                'error' => '予約の削除に失敗しました',
                'details' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * 予約を登録
     */
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

            return response()->json($booking, 201, [], JSON_UNESCAPED_UNICODE);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('バリデーションエラー:', ['message' => $e->getMessage()]);
            return response()->json([
                'error' => 'バリデーションエラー',
                'details' => $e->errors()
            ], 422, [], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            Log::error('予約エラー:', ['message' => $e->getMessage()]);
            return response()->json([
                'error' => '予約の登録に失敗しました',
                'details' => $e->getMessage()
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
