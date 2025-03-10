@extends('layouts.app')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8 flex justify-between items-center">
            <h2 class="text-2xl font-semibold text-gray-800">予約カレンダー</h2>
            <a href="{{ route('bookings.list') }}"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                </svg>
                予約一覧を表示
            </a>
        </div>
        <div class="bg-white shadow-lg rounded-lg p-6">
            <div id="calendar"></div>
        </div>
    </div>

    <!-- CSRFトークンを<meta>タグで設定 -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- モーダルオーバーレイ -->
    <div id="modalOverlay"
        class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden transition-opacity duration-300 ease-in-out"></div>

    <!-- モーダル -->
    <div id="bookingModal"
        class="fixed left-1/2 top-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white rounded-lg shadow-xl z-50 w-full max-w-md p-6 hidden transition-all duration-300 ease-in-out">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">予約を追加</h3>

        <div class="mb-4">
            <label for="bookingTitle" class="block text-sm font-medium text-gray-700 mb-1">予約タイトル:</label>
            <input type="text" id="bookingTitle"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                placeholder="予約タイトル" value="オンラインミーティング">
        </div>

        <div class="mb-4">
            <label for="timeSlot" class="block text-sm font-medium text-gray-700 mb-1">時間:</label>
            <select id="timeSlot"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                @for ($hour = 10; $hour < 24; $hour++)
                    <option value="{{ sprintf('%02d:00:00', $hour) }}">{{ sprintf('%02d:00', $hour) }} -
                        {{ sprintf('%02d:30', $hour) }}</option>
                    <option value="{{ sprintf('%02d:30:00', $hour) }}">{{ sprintf('%02d:30', $hour) }} -
                        {{ sprintf('%02d:00', $hour + 1) }}</option>
                @endfor
            </select>
        </div>

        <div class="mb-4">
            <label for="participantEmail" class="block text-sm font-medium text-gray-700 mb-1">参加者メールアドレス:</label>
            <input type="email" id="participantEmail"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                placeholder="参加者のメールアドレス">
        </div>

        <div class="mb-4 flex items-center">
            <input type="checkbox" id="waitingRoom"
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded" checked>
            <label for="waitingRoom" class="ml-2 block text-sm text-gray-700">待機室を有効にする</label>
        </div>

        <div class="mb-6 flex items-center">
            <input type="checkbox" id="createZoomMeeting"
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded" checked>
            <label for="createZoomMeeting" class="ml-2 block text-sm text-gray-700">Zoom会議を自動作成する</label>
        </div>

        <div class="flex justify-end space-x-3">
            <button id="closeModal"
                class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                キャンセル
            </button>
            <button id="saveBooking"
                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                予約する
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var selectedDate = '';
            // 🎨 視認性の高いカラフルな色リスト
            var colors = [
                "#E63946", // 赤（明るめ）
                "#F4A261", // オレンジ
                "#2A9D8F", // 緑（深め）
                "#264653", // 青（ダーク）
                "#457B9D", // 青（やや明るめ）
                "#8A4FFF", // 紫
                "#E76F51", // ピンク系オレンジ
                "#D62828", // 深い赤
                "#1D3557", // 濃い青
                "#F77F00", // 濃いオレンジ
            ];

            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                selectable: true,
                editable: true,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title', // ✅ ここに表示される
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },

                buttonText: {
                    today: '今日', // 'today' ボタンの表示を変更
                    month: '月表示', // 'month' ボタンを '月表示' に変更
                    week: '週表示', // 'week' ボタンを '週表示' に変更
                    day: '日表示' // 'day' ボタンを '日表示' に変更
                },

                titleFormat: { // ✅ タイトルのフォーマットを変更
                    year: 'numeric', // 西暦（例: 2025）
                    month: 'long' // 月のフルネーム（例: "March"）
                },

                eventTimeFormat: {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false
                },

                // ✅ 日本語の月名を追加
                datesSet: function(info) {
                    const monthNames = {
                        "January": "1月",
                        "February": "2月",
                        "March": "3月",
                        "April": "4月",
                        "May": "5月",
                        "June": "6月",
                        "July": "7月",
                        "August": "8月",
                        "September": "9月",
                        "October": "10月",
                        "November": "11月",
                        "December": "12月"
                    };

                    // 現在のタイトルを取得（例: "March 2025"）
                    let titleElement = document.querySelector('.fc-toolbar-title');
                    if (titleElement) {
                        let currentTitle = titleElement.textContent; // 現在のタイトル（英語）

                        // 英語の月を取得して日本語に変換
                        Object.keys(monthNames).forEach(englishMonth => {
                            if (currentTitle.includes(englishMonth)) {
                                let japaneseMonth = monthNames[englishMonth];
                                titleElement.textContent = currentTitle + " (" + japaneseMonth +
                                    ")";
                            }
                        });
                    }
                },
            
            // ✅ カレンダーの日付の数字だけをカラフルにする
            dayCellDidMount: function(info) {
                    let dayNumberEl = info.el.querySelector('.fc-daygrid-day-number');
                    if (dayNumberEl) {
                        let randomColor = colors[Math.floor(Math.random() * colors.length)];
                        dayNumberEl.style.color = randomColor; // 数字の色を変更
                        dayNumberEl.style.fontWeight = "bold"; // 太字にする
                        dayNumberEl.style.fontSize = "1.2em"; // 少し大きめに
                    }
                },
                // ✅ 予約データ取得時のフォーマットを修正
                events: function(fetchInfo, successCallback, failureCallback) {
                    fetch('/bookings')
                        .then(response => response.json())
                        .then(data => {
                            console.log("取得したイベントデータ:", data); // ✅ デバッグログ
                            let formattedEvents = data.map(event => ({
                                id: event.id,
                                title: "予約",
                                start: event.start,
                                end: event.end
                            }));
                            successCallback(formattedEvents);
                        })
                        .catch(error => {
                            console.error("イベントの取得エラー:", error);
                            failureCallback(error);
                        });
                },

                dateClick: function(info) {
                    selectedDate = info.dateStr;
                    console.log("選択された日付:", selectedDate);
                    openModal();
                },

                // ✅ 予約削除機能を追加
                eventClick: function(info) {
                    console.log("削除対象の予約 ID:", info.event.id); // ✅ デバッグログ

                    if (!info.event.id) {
                        alert("予約IDが取得できません。削除できません。");
                        return;
                    }

                    if (confirm("この予約を削除しますか？")) {
                        fetch(`/bookings/${info.event.id}`, {
                                method: 'DELETE',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector(
                                        'meta[name="csrf-token"]').getAttribute('content')
                                }
                            })
                            .then(response => {
                                if (!response.ok) {
                                    return response.json().then(errorData => {
                                        throw new Error(errorData.error || "予約の削除に失敗しました");
                                    });
                                }
                                return response.json();
                            })
                            .then(data => {
                                console.log("予約削除成功:", data);
                                calendar.refetchEvents(); // ✅ 削除後にカレンダーを更新
                                alert("予約を削除しました。一覧ページを開いている場合は再読み込みしてください。");
                            })
                            .catch(error => {
                                console.error("エラー:", error);
                                alert("予約の削除に失敗しました。エラー: " + error.message);
                            });
                    }
                }
        });

        calendar.render();

        function openModal() {
            console.log("モーダルを開く");
            let modal = document.getElementById('bookingModal');
            let overlay = document.getElementById('modalOverlay');

            modal.classList.remove('hidden');
            overlay.classList.remove('hidden');

            setTimeout(() => {
                modal.classList.add('active');
                overlay.classList.add('active');
            }, 10);
        }

        function closeModal() {
            let modal = document.getElementById('bookingModal');
            let overlay = document.getElementById('modalOverlay');

            modal.classList.remove('active');
            overlay.classList.remove('active');

            setTimeout(() => {
                modal.classList.add('hidden');
                overlay.classList.add('hidden');
            }, 300);
        }

        document.getElementById('closeModal').addEventListener('click', closeModal);
        document.getElementById('modalOverlay').addEventListener('click', closeModal);

        document.getElementById('saveBooking').addEventListener('click', function() {
        if (!selectedDate) {
            alert("日付が選択されていません");
            return;
        }

        var selectedTime = document.getElementById('timeSlot').value;
        var startTime = selectedDate + 'T' + selectedTime;
        var startDateTime = new Date(startTime);
        var endDateTime = new Date(startDateTime.getTime() + (30 * 60 * 1000));

        // ✅ タイトルを取得
        var bookingTitle = document.getElementById('bookingTitle').value || "オンラインミーティング";

        // ✅ Laravel で適切に処理できる `YYYY-MM-DD HH:MM:SS` に変換
        var formattedStart = startDateTime.getFullYear() + "-" +
            ("0" + (startDateTime.getMonth() + 1)).slice(-2) + "-" +
            ("0" + startDateTime.getDate()).slice(-2) + " " +
            ("0" + startDateTime.getHours()).slice(-2) + ":" +
            ("0" + startDateTime.getMinutes()).slice(-2) + ":00";

        var formattedEnd = endDateTime.getFullYear() + "-" +
            ("0" + (endDateTime.getMonth() + 1)).slice(-2) + "-" +
            ("0" + endDateTime.getDate()).slice(-2) + " " +
            ("0" + endDateTime.getHours()).slice(-2) + ":" +
            ("0" + endDateTime.getMinutes()).slice(-2) + ":00";

        // Zoom関連の情報を取得
        var participantEmail = document.getElementById('participantEmail').value;
        var waitingRoom = document.getElementById('waitingRoom').checked;
        var createZoomMeeting = document.getElementById('createZoomMeeting').checked;

        console.log("送信データ:", JSON.stringify({
            title: bookingTitle,
            start: formattedStart,
            end: formattedEnd,
            participant_email: participantEmail,
            waiting_room: waitingRoom,
            create_zoom_meeting: createZoomMeeting
        }));

        fetch('/bookings', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                        .getAttribute('content')
                },
                body: JSON.stringify({
                    title: bookingTitle,
                    start: formattedStart,
                    end: formattedEnd,
                    participant_email: participantEmail,
                    waiting_room: waitingRoom,
                    create_zoom_meeting: createZoomMeeting
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log("予約成功:", data);
                if (data.zoom_meeting_url) {
                    alert("Zoom会議URLが生成されました: " + data.zoom_meeting_url);
                }
                calendar.refetchEvents(); // ✅ 予約登録後にカレンダーを更新
                console.log("カレンダー更新完了");
                closeModal();
            })
            .catch(error => {
                console.error("エラー:", error);
                alert("予約の登録に失敗しました。エラー: " + error.message);
            });
        });
        });
    </script>


    <style>
        /* モーダルアニメーション用 */
        #bookingModal.active {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1);
        }

        #bookingModal {
            opacity: 0;
            transform: translate(-50%, -50%) scale(0.95);
        }

        #modalOverlay.active {
            opacity: 1;
        }

        #modalOverlay {
            opacity: 0;
        }

        /* FullCalendarのスタイル調整 */
        .fc .fc-toolbar-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1f2937;
        }

        .fc .fc-button-primary {
            background-color: #4f46e5;
            border-color: #4f46e5;
        }

        .fc .fc-button-primary:hover {
            background-color: #4338ca;
            border-color: #4338ca;
        }

        .fc .fc-button-primary:disabled {
            background-color: #6366f1;
            border-color: #6366f1;
        }

        .fc-day-today {
            background-color: #eef2ff !important;
        }

        .fc-event {
            background-color: #4f46e5;
            border-color: #4f46e5;
            padding: 2px 4px;
            border-radius: 4px;
        }

        .fc-daygrid-day:hover {
            background-color: #f9fafb;
            cursor: pointer;
        }
    </style>
@endsection
