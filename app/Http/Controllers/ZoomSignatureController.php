<?php

namespace App\Http\Controllers;

use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ZoomSignatureController extends Controller
{
    /**
     * Zoom SDK 用の署名を生成して返す
     */
    public function generate(Request $request)
    {
        if (!$request->isMethod('post')) {
            return response()->json(['error' => 'Method Not Allowed'], 405);
        }

        // 🔹 バリデーションチェック
        $validator = Validator::make($request->all(), [
            'meeting_number' => 'required|string',
            'role' => 'nullable|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid request parameters'], 400);
        }

        // 🔹 入力値の取得
        $meetingNumber = $request->input('meeting_number');
        $role = (int) $request->input('role', 0);

        // 🔹 .env から Zoom SDK Key / Secret を取得
        $apiKey = config('services.zoom.sdk_key');
        $apiSecret = config('services.zoom.sdk_secret');

        if (empty($apiKey) || empty($apiSecret)) {
            Log::error("❌ Zoom SDK の APIキーまたはシークレットが設定されていません");
            return response()->json(['error' => 'Zoom SDK API credentials are missing'], 500);
        }

        // 🔹 タイムスタンプ設定
        $iat = time();                    // 発行時間
        $exp = $iat + 120;               // 有効期限（2分）
        $tokenExp = $iat + (60 * 60);    // トークンの有効期限（1時間）

        // 🔹 Zoom署名用のペイロードを作成
        $payload = [
            'api_key'   => $apiKey,
            'mn'        => $meetingNumber,
            'role'      => $role,
            'iat'       => $iat,
            'exp'       => $exp,
            'tokenExp'  => $tokenExp,
        ];

        try {
            // 🔹 JWT署名を生成
            $signature = JWT::encode($payload, $apiSecret, 'HS256');

            Log::info("✅ Zoom 署名を正常に生成: meeting_number={$meetingNumber}");

            return response()->json([
                'signature' => $signature
            ]);
        } catch (\Exception $e) {
            Log::error("❌ Zoom 署名の生成に失敗: " . $e->getMessage());

            return response()->json([
                'error' => 'Failed to generate signature',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
