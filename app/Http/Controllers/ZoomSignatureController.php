<?php

namespace App\Http\Controllers;

use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ZoomSignatureController extends Controller
{
    public function generate(Request $request)
    {
        $meetingNumber = $request->query('meeting_number');
        $apiKey = env('ZOOM_MEETING_SDK_KEY');
        $apiSecret = env('ZOOM_MEETING_SDK_SECRET');
        $role = 0; // 0 = 参加者, 1 = ホスト

        $iat = time();
        $exp = $iat + 60 * 2; // 2分間有効

        $payload = [
            'sdkKey' => $apiKey,
            'mn' => $meetingNumber,
            'role' => $role,
            'iat' => $iat,
            'exp' => $exp,
            'tokenExp' => $exp
        ];

        $signature = JWT::encode($payload, $apiSecret, 'HS256');

        return response()->json(['signature' => $signature]);
    }
}
