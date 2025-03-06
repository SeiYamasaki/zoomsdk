@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>予約カレンダー</h2>
        <div id="calendar"></div>
    </div>

    <!-- CSRFトークンを<meta>タグで設定 -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- モーダルオーバーレイ -->
    <div id="modalOverlay" class="modal-overlay"></div>

    <!-- モーダル -->
    <div id="bookingModal" class="modal">
        <h3 class="modal-title">予約を追加</h3>

        <label class="modal-label">時間:</label>
        <select id="timeSlot" class="modal-select">
            @for ($hour = 10; $hour < 24; $hour++)
                <option value="{{ sprintf('%02d:00:00', $hour) }}">{{ sprintf('%02d:00', $hour) }} -
                    {{ sprintf('%02d:30', $hour) }}</option>
                <option value="{{ sprintf('%02d:30:00', $hour) }}">{{ sprintf('%02d:30', $hour) }} -
                    {{ sprintf('%02d:00', $hour + 1) }}</option>
            @endfor
        </select>

        <div class="modal-actions">
            <button id="saveBooking" class="btn btn-primary">予約する</button>
            <button id="closeModal" class="btn btn-secondary">キャンセル</button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var selectedDate = '';

            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                selectable: true,
                editable: true,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: '/bookings',

                dateClick: function(info) {
                    selectedDate = info.dateStr;
                    console.log("選択された日付:", selectedDate);
                    openModal();
                },

                // **予約削除機能**
                eventClick: function(info) {
                    console.log("削除対象の予約 ID:", info.event.id); // **予約IDの確認**

                    if (!info.event.id) {
                        alert("予約IDが取得できません。削除できません。");
                        return;
                    }

                    if (confirm("この予約を削除しますか？")) {
                        fetch(`/api/bookings/${info.event.id}`, { // 👈 `api/` を追加
                                method: 'DELETE',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector(
                                        'meta[name="csrf-token"]').getAttribute('content')
                                }
                            })

                            .then(response => {
                                // **レスポンスが JSON かどうかを確認**
                                const contentType = response.headers.get("content-type");
                                if (!response.ok) {
                                    if (contentType && contentType.includes("application/json")) {
                                        return response.json().then(errorData => {
                                            throw new Error(errorData.error ||
                                                "予約の削除に失敗しました");
                                        });
                                    } else {
                                        return response.text().then(text => {
                                            throw new Error("サーバーエラー: " + text);
                                        });
                                    }
                                }
                                return response.json();
                            })
                            .then(data => {
                                console.log("予約削除成功:", data);

                                // **削除後にカレンダーを更新**
                                calendar.refetchEvents();
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
                let modal = document.getElementById('bookingModal');
                let overlay = document.getElementById('modalOverlay');

                modal.style.display = "block";
                overlay.style.display = "block";

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
                    modal.style.display = "none";
                    overlay.style.display = "none";
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

                var formattedStart = startDateTime.toISOString().slice(0, 19);
                var formattedEnd = endDateTime.toISOString().slice(0, 19);

                console.log("送信データ:", {
                    title: "予約",
                    start: formattedStart,
                    end: formattedEnd
                });

                fetch('/bookings', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .getAttribute('content')
                        },
                        body: JSON.stringify({
                            title: "予約",
                            start: formattedStart,
                            end: formattedEnd
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log("予約成功:", data);
                        calendar.refetchEvents();
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
        /* オーバーレイ */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }

        /* モーダル */
        .modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.9);
            background: rgba(255, 255, 255, 0.8);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            text-align: center;
            width: 400px;
            z-index: 1000;
            opacity: 0;
            transition: transform 0.3s ease-out, opacity 0.3s ease-out;
            backdrop-filter: blur(10px);
        }

        .modal.active {
            transform: translate(-50%, -50%) scale(1);
            opacity: 1;
        }

        /* セレクトボックス */
        .modal-select {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            text-align-last: center;
        }

        /* ボタン */
        .btn {
            padding: 12px 16px;
            border: none;
            cursor: pointer;
            font-size: 15px;
            border-radius: 8px;
            transition: all 0.3s ease-in-out;
        }

        .btn-primary {
            background: linear-gradient(135deg, #007bff, #004494);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #0056b3, #002c76);
            transform: translateY(-3px);
        }

        .btn-secondary {
            background: #ddd;
            color: #333;
        }

        .btn-secondary:hover {
            background: rgba(200, 200, 200, 0.6);
        }
    </style>
@endsection
