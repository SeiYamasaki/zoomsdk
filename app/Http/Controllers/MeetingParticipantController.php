<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\MeetingParticipant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MeetingParticipantController extends Controller
{
    /**
     * ミーティング参加フォームを表示
     */
    public function showRegistrationForm($id)
    {
        $booking = Booking::findOrFail($id);
        return view('meetings.register', compact('booking'));
    }

    /**
     * ミーティングへの参加を登録
     */
    public function register(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);

        // バリデーション
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ]);

        // 既に登録していないかチェック
        $existingParticipant = MeetingParticipant::where('booking_id', $booking->id)
            ->where('email', $validated['email'])
            ->first();

        if ($existingParticipant) {
            return redirect()->route('meetings.registration.complete', ['token' => $existingParticipant->access_token])
                ->with('message', '既に登録されています。');
        }

        // 新規参加者を登録
        $accessToken = MeetingParticipant::generateAccessToken();

        $participant = new MeetingParticipant([
            'booking_id' => $booking->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'access_token' => $accessToken,
        ]);

        $participant->save();

        // メール送信処理を追加することも可能（実装は省略）

        return redirect()->route('meetings.registration.complete', ['token' => $accessToken]);
    }

    /**
     * 登録完了・ミーティング参加情報表示
     */
    public function showRegistrationComplete($token)
    {
        $participant = MeetingParticipant::where('access_token', $token)->firstOrFail();
        $booking = $participant->booking;

        return view('meetings.complete', compact('participant', 'booking'));
    }
}
