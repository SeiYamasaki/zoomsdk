@extends('layouts.app')

@section('content')
    <div class="max-w-5xl mx-auto px-4 py-12">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">Zoom ミーティングに参加</h2>

        <div id="zoomContainer" class="border p-4 shadow-md rounded-md bg-gray-50">
            <p class="text-gray-700">ミーティングに参加する準備をしています...</p>
        </div>

        <button id="joinMeetingBtn" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
            ミーティングに参加
        </button>
    </div>

    <script src="https://source.zoom.us/2.12.0/lib/vendor/react.min.js"></script>
    <script src="https://source.zoom.us/zoom-meeting-2.12.0.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let joinButton = document.getElementById("joinMeetingBtn");

            joinButton.addEventListener("click", function() {
                startZoomMeeting();
            });

            function startZoomMeeting() {
                ZoomMtg.setZoomJSLib('https://source.zoom.us/2.12.0/lib', '/av');
                ZoomMtg.preLoadWasm();
                ZoomMtg.prepareJssdk();

                fetch('/api/zoom-signature?meeting_number={{ $meetingId }}')
                    .then(response => response.json())
                    .then(data => {
                        if (data.signature) {
                            ZoomMtg.init({
                                leaveUrl: '{{ route('dashboard') }}',
                                isSupportAV: true,
                                success: function() {
                                    ZoomMtg.join({
                                        meetingNumber: '{{ $meetingId }}',
                                        userName: '{{ Auth::user()->name }}',
                                        signature: data.signature,
                                        apiKey: '{{ env('ZOOM_MEETING_SDK_KEY') }}',
                                        userEmail: '{{ Auth::user()->email }}',
                                        passWord: '{{ $meetingPassword }}',
                                        success: function() {
                                            console.log("Zoom ミーティング参加成功");
                                        },
                                        error: function(error) {
                                            console.error("Zoom ミーティング参加エラー", error);
                                        }
                                    });
                                }
                            });
                        } else {
                            alert("Zoom ミーティングの署名を取得できませんでした");
                        }
                    })
                    .catch(error => console.error("API 呼び出しエラー", error));
            }
        });
    </script>
@endsection
