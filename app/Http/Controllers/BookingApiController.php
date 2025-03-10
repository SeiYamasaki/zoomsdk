<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Services\ZoomService;

class BookingApiController extends Controller
{
    protected $zoomService;

    public function __construct(ZoomService $zoomService)
    {
        $this->zoomService = $zoomService;
    }

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
     * 予約一覧を取得 (管理画面用)
     */
    public function list()
    {
        $bookings = Booking::orderBy('start', 'desc')->get();
        return response()->json($bookings, 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * 予約を削除
     */
    public function destroy($id)
    {
        try {
            Log::info("予約削除リクエスト受信: ID=$id");

            // IDが数値かどうかチェック
            if (!is_numeric($id)) {
                Log::error("無効な予約ID: $id");
                return response()->json([
                    'error' => '無効な予約IDです'
                ], 400);
            }

            // 予約を検索
            $booking = Booking::find($id);
            if (!$booking) {
                Log::warning("予約削除エラー (存在しないID): ID=$id");
                return response()->json([
                    'error' => '予約が見つかりません'
                ], 404);
            }

            // ZoomミーティングIDがある場合、Zoom APIを使用して会議を削除
            if ($booking->zoom_meeting_id) {
                $deleteResult = $this->zoomService->deleteMeeting($booking->zoom_meeting_id);
                if (!$deleteResult) {
                    Log::warning("Zoom会議の削除に失敗しましたが、予約は削除します: Meeting ID={$booking->zoom_meeting_id}");
                }
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

            // バリデーション
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'start' => 'required|date_format:Y-m-d H:i:s',
                'end' => 'required|date_format:Y-m-d H:i:s|after:start',
                'host_user_id' => 'nullable|string',
                'participant_email' => 'nullable|email',
                'waiting_room' => 'nullable|boolean',
                'max_participants' => 'nullable|integer|min:1|max:100',
            ]);

            // データフォーマット統一 (日本時間に変換)
            $validated['start'] = Carbon::parse($validated['start'])->setTimezone('Asia/Tokyo')->format('Y-m-d H:i:s');
            $validated['end'] = Carbon::parse($validated['end'])->setTimezone('Asia/Tokyo')->format('Y-m-d H:i:s');

            // デフォルト値の設定
            $validated['waiting_room'] = $validated['waiting_room'] ?? true;
            $validated['max_participants'] = $validated['max_participants'] ?? 10;

            // Zoom会議の作成
            $zoomMeeting = $this->zoomService->createMeeting([
                'title' => $validated['title'],
                'start' => $validated['start'],
                'end' => $validated['end'],
                'waiting_room' => $validated['waiting_room'],
            ]);

            if ($zoomMeeting) {
                // Zoom会議情報を追加
                $validated['zoom_meeting_id'] = $zoomMeeting['id'];
                $validated['zoom_meeting_url'] = $zoomMeeting['join_url'];
                $validated['zoom_meeting_password'] = $zoomMeeting['password'] ?? null;
            } else {
                Log::warning('Zoom会議の作成に失敗しましたが、予約は作成します。');
            }

            // データの保存
            $booking = new Booking();
            $booking->fill($validated);
            $booking->save();

            Log::info('予約成功:', ['id' => $booking->id, 'zoom_meeting_id' => $booking->zoom_meeting_id ?? 'なし']);

            return response()->json($booking, 201, [], JSON_UNESCAPED_UNICODE);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('バリデーションエラー:', ['details' => $e->errors()]);
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
