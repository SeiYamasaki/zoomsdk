@extends('layouts.app')

@section('content')
    <div class="max-w-5xl mx-auto px-4 py-12">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">Zoom ミーティングに参加</h2>

        <!-- ✅ Laravel `.env` の `sdkKey` をデバッグ用に表示 -->
        <p>SDK Key (from Laravel): {{ config('services.zoom.sdk_key') }}</p> <!-- Laravel 側の `sdk_key` を表示 -->

        <div id="zoomContainer" data-meeting-id="{{ $meetingId }}" data-meeting-password="{{ $meetingPassword }}"
            data-api-key="{{ config('services.zoom.sdk_key') }}" data-user-name="{{ Auth::user()->name ?? 'ゲストユーザー' }}">


            <button id="joinMeetingBtn" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                ミーティングに参加
            </button>
        </div>

        <!-- ✅ Zoom Meeting SDK (CDN 版) -->
        <script src="https://source.zoom.us/3.12.0/lib/vendor/react.min.js"></script>
        <script src="https://source.zoom.us/3.12.0/lib/vendor/react-dom.min.js"></script>
        <script src="https://source.zoom.us/3.12.0/lib/vendor/redux.min.js"></script>
        <script src="https://source.zoom.us/3.12.0/lib/vendor/redux-thunk.min.js"></script>
        <script src="https://source.zoom.us/3.12.0/lib/vendor/lodash.min.js"></script>
        <script src="https://source.zoom.us/3.12.0/zoom-meeting-embedded-3.12.0.min.js"></script>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                console.log("✅ Zoom Meeting SDK のロード確認中...");

                if (!window.ZoomMtgEmbedded) {
                    console.error("❌ ZoomMtgEmbedded がロードされていません");
                    return;
                }

                const client = window.ZoomMtgEmbedded.createClient();
                console.log("✅ Zoom Meeting SDK は正常にロードされました:", client);

                client.init({
                    zoomAppRoot: document.getElementById("zoomContainer"),
                    language: "jp-JP",
                    dependencies: ["web", "audio", "video"]
                }).then(() => {
                    console.log("✅ Zoom Meeting SDK 初期化成功");

                    document.getElementById("joinMeetingBtn").addEventListener("click", async function() {
                        await startZoomMeeting(client);
                    });

                }).catch(error => {
                    console.error("❌ Zoom Meeting SDK 初期化エラー:", error);
                });
            });

            async function startZoomMeeting(client) {
                console.log("📡 `client.join()` の前にデータを確認");

                const zoomContainer = document.getElementById("zoomContainer");
                const meetingId = zoomContainer.dataset.meetingId;
                const meetingPassword = zoomContainer.dataset.meetingPassword;
                const userName = zoomContainer.dataset.userName || "ゲストユーザー";
                const sdkKey = zoomContainer.dataset.apiKey;

                console.log("📡 Meeting ID:", meetingId);
                console.log("📡 Meeting Password:", meetingPassword);
                console.log("📡 User Name:", userName);
                console.log("📡 SDK Key (API Key):", sdkKey);

                if (!meetingId || !sdkKey || sdkKey === "null" || sdkKey === "" || sdkKey === "MISSING_SDK_KEY") {
                    console.error("❌ SDK Key または Meeting ID が設定されていません");
                    alert("SDK Key または Meeting ID が設定されていません。Laravel の `.env` を確認してください。");
                    return;
                }

                console.log("📡 署名を取得中...");

                try {
                    const response = await fetch(`/api/zoom-signature`, {
                        method: "POST",
                        headers: {
                            "Accept": "application/json",
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({
                            meeting_number: meetingId
                        })
                    });

                    console.log("📡 `fetch()` のレスポンスステータス:", response.status);

                    if (!response.ok) {
                        throw new Error(`サーバーエラー: ${response.status} ${response.statusText}`);
                    }

                    const data = await response.json();
                    console.log("📡 取得した署名:", data.signature);

                    if (!data.signature) {
                        console.error("❌ Zoom ミーティングの署名を取得できませんでした");
                        alert("❌ Zoom ミーティングの署名を取得できませんでした");
                        return;
                    }

                    console.log("⚙️ `client.join()` 実行開始...");

                    try {
                        await client.join({
                            sdkKey: sdkKey,
                            signature: data.signature,
                            meetingNumber: meetingId,
                            userName: userName,
                            password: meetingPassword,
                            success: () => {
                                console.log("✅ Zoom ミーティング参加成功 🎉");
                                document.getElementById("statusMessage").textContent = "✅ Zoom ミーティングに参加しました";
                            },
                            error: (joinError) => {
                                console.error("❌ Zoom ミーティング参加エラー:", joinError);
                                alert(`Zoom ミーティングの参加に失敗しました: ${joinError.reason || joinError.message}`);
                            }
                        });

                    } catch (joinError) {
                        console.error("❌ Zoom ミーティング参加エラー:", joinError);
                    }

                } catch (error) {
                    console.error("❌ Zoom ミーティング署名の取得エラー:", error);
                }
            }
        </script>
    @endsection
