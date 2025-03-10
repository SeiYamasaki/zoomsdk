<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ZoomService
{
    protected $clientId;
    protected $clientSecret;
    protected $accountId;
    protected $baseUrl;

    public function __construct()
    {
        $this->clientId = trim(env('ZOOM_CLIENT_ID', ''));
        $this->clientSecret = trim(env('ZOOM_CLIENT_SECRET', ''));
        $this->accountId = trim(env('ZOOM_ACCOUNT_ID', ''));
        $this->baseUrl = env('ZOOM_API_BASE_URL', 'https://api.zoom.us/v2');

        // `.env` の設定がない場合はエラーログを記録
        if (empty($this->clientId) || empty($this->clientSecret) || empty($this->accountId)) {
            Log::error('Zoom API credentials are missing', [
                'ZOOM_CLIENT_ID' => $this->clientId ?: 'Not Set',
                'ZOOM_CLIENT_SECRET' => $this->clientSecret ? '*****' : 'Not Set',
                'ZOOM_ACCOUNT_ID' => $this->accountId ?: 'Not Set',
            ]);
        }
    }

    /**
     * Zoom API のアクセストークンを取得
     */
    private function getAccessToken()
    {
        // `.env` の設定がない場合、処理を停止
        if (empty($this->clientId) || empty($this->clientSecret) || empty($this->accountId)) {
            return [
                'error' => 'Zoom API credentials are missing',
                'client_id' => $this->clientId ?: 'Not Set',
                'client_secret' => !empty($this->clientSecret) ? '*****' : 'Not Set',
                'account_id' => $this->accountId ?: 'Not Set'
            ];
        }

        // Zoom API に認証リクエストを送信
        $response = Http::asForm()->withHeaders([
            'Authorization' => 'Basic ' . base64_encode("{$this->clientId}:{$this->clientSecret}"),
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])->post('https://zoom.us/oauth/token', [
            'grant_type' => 'account_credentials',
            'account_id' => $this->accountId,
        ]);

        // API レスポンスのデバッグ（エラー時）
        if ($response->failed()) {
            Log::error('Failed to obtain Zoom API access token', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);
            return [
                'error' => 'Failed to obtain access token',
                'status' => $response->status(),
                'response' => $response->json()
            ];
        }

        return $response->json()['access_token'] ?? null;
    }

    /**
     * Zoom ミーティングを作成
     */
    public function createMeeting($topic = 'Laravel Zoom Meeting', $startTime = null)
    {
        $accessToken = $this->getAccessToken();

        // アクセストークンが取得できなかった場合のエラーハンドリング
        if (!$accessToken || !is_string($accessToken)) {
            Log::error('Invalid or missing access token', [
                'access_token' => $accessToken
            ]);
            return [
                'error' => 'Invalid or missing access token',
                'access_token' => $accessToken
            ];
        }

        $response = Http::withToken($accessToken)
            ->post("{$this->baseUrl}/users/me/meetings", [
                'topic' => $topic,
                'type' => 2, // 予約ミーティング
                'start_time' => $startTime ?? now()->toIso8601String(),
                'duration' => 60,
                'timezone' => 'Asia/Tokyo',
                'agenda' => 'Laravel Zoom Integration',
                'settings' => [
                    'host_video' => true,
                    'participant_video' => true,
                    'waiting_room' => true,
                ],
            ]);

        // API リクエストのエラーハンドリング
        if ($response->failed()) {
            Log::error('Zoom API request failed', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);
            return [
                'error' => 'Zoom API request failed',
                'status' => $response->status(),
                'response' => $response->json()
            ];
        }

        return $response->json();
    }

    /**
     * Zoom ミーティングの詳細を取得
     */
    public function getMeeting($meetingId)
    {
        $accessToken = $this->getAccessToken();

        // アクセストークンが取得できなかった場合のエラーハンドリング
        if (!$accessToken || !is_string($accessToken)) {
            Log::error('Invalid or missing access token for getting meeting details', [
                'access_token' => $accessToken
            ]);
            return false;
        }

        try {
            $response = Http::withToken($accessToken)
                ->get("{$this->baseUrl}/meetings/{$meetingId}");

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('Failed to get Zoom meeting details', [
                    'meeting_id' => $meetingId,
                    'status' => $response->status(),
                    'response' => $response->json()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception when getting Zoom meeting details', [
                'meeting_id' => $meetingId,
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Zoom ミーティングを削除
     */
    public function deleteMeeting($meetingId)
    {
        $accessToken = $this->getAccessToken();

        // アクセストークンが取得できなかった場合のエラーハンドリング
        if (!$accessToken || !is_string($accessToken)) {
            Log::error('Invalid or missing access token for deleting meeting', [
                'access_token' => $accessToken
            ]);
            return false;
        }

        try {
            $response = Http::withToken($accessToken)
                ->delete("{$this->baseUrl}/meetings/{$meetingId}");

            if ($response->successful()) {
                Log::info("Zoom meeting deleted successfully", [
                    'meeting_id' => $meetingId
                ]);
                return true;
            } else {
                Log::error("Failed to delete Zoom meeting", [
                    'meeting_id' => $meetingId,
                    'status' => $response->status(),
                    'response' => $response->json()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Exception when deleting Zoom meeting", [
                'meeting_id' => $meetingId,
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }
}
