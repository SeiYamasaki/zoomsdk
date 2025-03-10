<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ZoomService
{
    protected $accountId;
    protected $clientId;
    protected $clientSecret;
    protected $apiBaseUrl;
    protected $accessToken;

    public function __construct()
    {
        $this->accountId = config('services.zoom.account_id');
        $this->clientId = config('services.zoom.client_id');
        $this->clientSecret = config('services.zoom.client_secret');
        $this->apiBaseUrl = config('services.zoom.api_base_url');
        $this->accessToken = $this->getAccessToken();
    }

    /**
     * Zoomアクセストークンを取得する
     * キャッシュを利用して、有効期限内のトークンを再利用
     */
    protected function getAccessToken()
    {
        // キャッシュからトークンを取得
        if (Cache::has('zoom_access_token')) {
            return Cache::get('zoom_access_token');
        }

        try {
            // OAuth認証の前にデバッグログ
            Log::debug('Zoomアクセストークン取得リクエスト準備', [
                'auth_url' => 'https://zoom.us/oauth/token',
                'account_id' => $this->accountId,
                'client_id' => substr($this->clientId, 0, 5) . '...',
                'client_secret' => substr($this->clientSecret, 0, 5) . '...',
            ]);

            // OAuth認証でアクセストークンを取得
            $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
                ->asForm()
                ->post('https://zoom.us/oauth/token', [
                    'grant_type' => 'account_credentials',
                    'account_id' => $this->accountId,
                ]);

            // レスポンスのデバッグログ
            Log::debug('Zoomアクセストークン取得レスポンス', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                $tokenData = $response->json();
                $accessToken = $tokenData['access_token'];
                $expiresIn = $tokenData['expires_in'] - 60; // 1分早く期限切れにする

                // トークンをキャッシュに保存
                Cache::put('zoom_access_token', $accessToken, now()->addSeconds($expiresIn));

                return $accessToken;
            } else {
                Log::error('Zoomアクセストークンの取得に失敗しました。', [
                    'status' => $response->status(),
                    'response' => $response->json()
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Zoom OAuth認証中に例外が発生しました。', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Zoomミーティングを作成する
     * 
     * @param array $data
     * @return mixed
     */
    public function createMeeting(array $data)
    {
        if (!$this->accessToken) {
            Log::error('Zoomアクセストークンがないため、会議を作成できません。');
            return false;
        }

        try {
            // リクエスト前のデバッグログ
            Log::debug('Zoom会議作成リクエスト準備', [
                'api_url' => $this->apiBaseUrl . 'users/me/meetings',
                'headers' => [
                    'Authorization' => 'Bearer ' . substr($this->accessToken, 0, 10) . '...',
                    'Content-Type' => 'application/json',
                ],
                'data' => [
                    'topic' => $data['title'] ?? 'オンラインミーティング',
                    'type' => 2,
                    'start_time' => date('Y-m-d\TH:i:s', strtotime($data['start'])),
                    'duration' => 30,
                    'timezone' => 'Asia/Tokyo'
                ]
            ]);

            // テスト用のモックデータを返す（本番環境では削除）
            // APIキーが設定されていない場合や開発環境ではモックデータを使用
            if (app()->environment('local') || !$this->accessToken) {
                Log::info('開発環境のためモックデータを使用します');
                return [
                    'id' => 'mock_' . time(),
                    'join_url' => 'https://zoom.us/j/1234567890?pwd=mockPassword',
                    'password' => 'mockPassword',
                    'start_url' => 'https://zoom.us/s/1234567890?zak=mock_token'
                ];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ])->post($this->apiBaseUrl . 'users/me/meetings', [
                'topic' => $data['title'] ?? 'オンラインミーティング',
                'type' => 2, // スケジュールされたミーティング
                'start_time' => date('Y-m-d\TH:i:s', strtotime($data['start'])),
                'duration' => 30, // 30分間
                'timezone' => 'Asia/Tokyo',
                'settings' => [
                    'host_video' => true,
                    'participant_video' => true,
                    'waiting_room' => $data['waiting_room'] ?? true,
                    'join_before_host' => false,
                    'mute_upon_entry' => true,
                    'watermark' => false,
                    'approval_type' => 0,
                    'registration_type' => 1,
                    'audio' => 'both',
                    'auto_recording' => 'none',
                ]
            ]);

            // レスポンスのデバッグログ
            Log::debug('Zoom会議作成レスポンス', [
                'status' => $response->status(),
                'body' => $response->body(),
                'json' => $response->json()
            ]);

            if ($response->successful()) {
                Log::info('Zoom会議が正常に作成されました。', [
                    'meeting_id' => $response->json('id'),
                    'join_url' => $response->json('join_url')
                ]);
                return $response->json();
            } else {
                Log::error('Zoom会議の作成中にエラーが発生しました。', [
                    'status' => $response->status(),
                    'response' => $response->json()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Zoom API呼び出し中に例外が発生しました。', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Zoom会議の詳細を取得する
     * 
     * @param string $meetingId
     * @return mixed
     */
    public function getMeeting($meetingId)
    {
        if (!$this->accessToken) {
            Log::error('Zoomアクセストークンがないため、会議情報を取得できません。');
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ])->get($this->apiBaseUrl . 'meetings/' . $meetingId);

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('Zoom会議の詳細取得中にエラーが発生しました。', [
                    'meeting_id' => $meetingId,
                    'status' => $response->status(),
                    'response' => $response->json()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Zoom API呼び出し中に例外が発生しました。', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Zoom会議を削除する
     * 
     * @param string $meetingId
     * @return mixed
     */
    public function deleteMeeting($meetingId)
    {
        if (!$this->accessToken) {
            Log::error('Zoomアクセストークンがないため、会議を削除できません。');
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ])->delete($this->apiBaseUrl . 'meetings/' . $meetingId);

            if ($response->successful()) {
                Log::info('Zoom会議が正常に削除されました。', [
                    'meeting_id' => $meetingId
                ]);
                return true;
            } else {
                Log::error('Zoom会議の削除中にエラーが発生しました。', [
                    'meeting_id' => $meetingId,
                    'status' => $response->status(),
                    'response' => $response->json()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Zoom API呼び出し中に例外が発生しました。', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
}
