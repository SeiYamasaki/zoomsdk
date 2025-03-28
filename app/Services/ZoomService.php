<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZoomService
{
    protected $clientId;
    protected $clientSecret;
    protected $accountId;
    protected $baseUrl;

    public function __construct()
    {
        $this->clientId = config('services.zoom.client_id');
        $this->clientSecret = config('services.zoom.client_secret');
        $this->accountId = config('services.zoom.account_id');
        $this->baseUrl = config('services.zoom.api_base_url', 'https://api.zoom.us/v2');

        if (empty($this->clientId) || empty($this->clientSecret) || empty($this->accountId)) {
            Log::error('Zoom API credentials are missing', [
                'ZOOM_CLIENT_ID' => $this->clientId ?: 'Not Set',
                'ZOOM_CLIENT_SECRET' => $this->clientSecret ? '*****' : 'Not Set',
                'ZOOM_ACCOUNT_ID' => $this->accountId ?: 'Not Set',
            ]);
        }
    }

    private function getAccessToken()
    {
        if (empty($this->clientId) || empty($this->clientSecret) || empty($this->accountId)) {
            return [
                'error' => 'Zoom API credentials are missing',
                'client_id' => $this->clientId ?: 'Not Set',
                'client_secret' => !empty($this->clientSecret) ? '*****' : 'Not Set',
                'account_id' => $this->accountId ?: 'Not Set'
            ];
        }

        $response = Http::asForm()->withHeaders([
            'Authorization' => 'Basic ' . base64_encode("{$this->clientId}:{$this->clientSecret}"),
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])->post('https://zoom.us/oauth/token', [
            'grant_type' => 'account_credentials',
            'account_id' => $this->accountId,
        ]);

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

    public function createMeeting($topic = 'Laravel Zoom Meeting', $startTime = null)
    {
        $accessToken = $this->getAccessToken();

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
                'type' => 1,
                'start_time' => $startTime ?? now()->toIso8601String(),
                'duration' => 60,
                'timezone' => 'Asia/Tokyo',
                'agenda' => 'Laravel Zoom Integration',
                'settings' => [
                    'host_video' => true,
                    'participant_video' => true,
                    'waiting_room' => false,           // ✅ SDK接続を妨げないように無効化
                    'join_before_host' => true,        // ✅ SDKでホストより先に入室可能に
                    'mute_upon_entry' => true,
                    'approval_type' => 0,
                    'auto_recording' => 'cloud',
                ],
            ]);

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

    public function getMeeting($meetingId)
    {
        $accessToken = $this->getAccessToken();

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

    public function deleteMeeting($meetingId)
    {
        $accessToken = $this->getAccessToken();

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
