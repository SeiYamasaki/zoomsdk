document.addEventListener("DOMContentLoaded", function () {
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
        dependencies: ["web", "audio", "video"],
        debug: true
    }).then(() => {
        console.log("✅ Zoom Meeting SDK 初期化成功");

        document.getElementById("joinMeetingBtn").addEventListener("click", async function () {
            await startZoomMeeting(client);
        });

    }).catch(error => {
        console.error("❌ Zoom Meeting SDK 初期化エラー:", error);
    });
});

async function startZoomMeeting(client) {
    const zoomContainer = document.getElementById("zoomContainer");
    const meetingId = zoomContainer.dataset.meetingId;
    const meetingPassword = zoomContainer.dataset.meetingPassword;
    const userName = zoomContainer.dataset.userName || "ゲストユーザー";
    const sdkKey = zoomContainer.dataset.apiKey;

    console.log("📡 Meeting Info", {
        meetingId,
        meetingPassword,
        userName,
        sdkKey
    });

    if (!meetingId || !sdkKey || sdkKey === "null" || sdkKey === "" || sdkKey === "MISSING_SDK_KEY") {
        alert("❌ SDK Key または Meeting ID が設定されていません。Laravel の .env を確認してください。");
        return;
    }

    try {
        const response = await fetch(`/api/zoom-signature`, {
            method: "POST",
            headers: {
                "Accept": "application/json",
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ meeting_number: meetingId })
        });

        const data = await response.json();

        if (!response.ok || !data.signature) {
            throw new Error("Zoom署名の取得に失敗しました");
        }

        console.log("📡 Zoom署名を取得:", data.signature);

        client.join({
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
                alert(`Zoom ミーティングの参加に失敗しました。\n詳細: ${JSON.stringify(joinError)}`);
            }
        });

    } catch (error) {
        console.error("❌ Zoom署名の取得エラー:", error);
        alert("Zoom署名の取得に失敗しました。ネットワークやサーバー側を確認してください。");
    }
}
