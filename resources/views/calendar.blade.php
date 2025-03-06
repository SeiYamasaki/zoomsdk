@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>予約カレンダー</h2>
        <div id="calendar"></div>
    </div>

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
                events: '/api/bookings', // ✅ Laravel API から正しいデータを取得

                eventTimeFormat: {
                    hour: '2-digit',
                    minute: '2-digit',
                    meridiem: false
                },
                eventDisplay: 'block', // ✅ デフォルトの時間表示を無効化（カスタムのタイトルのみ表示）

                // イベントデータをカスタムで表示
                eventContent: function(arg) {
                    return {
                        html: `<b>${arg.event.title}</b>`
                    }; // ✅ カスタムタイトルを適用
                },

                // 日付をクリックしたときの処理
                dateClick: function(info) {
                    selectedDate = info.dateStr;
                    openModal();
                },

                // 予約をクリックしたときの処理
                eventClick: function(info) {
                    if (confirm("この予約を削除しますか？")) {
                        fetch(`/api/bookings/${info.event.id}`, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector(
                                        'meta[name="csrf-token"]').getAttribute('content'),
                                    'Content-Type': 'application/json'
                                }
                            })
                            .then(response => {
                                if (!response.ok) {
                                    return response.text().then(text => {
                                        throw new Error("サーバーからのレスポンス: " + text);
                                    });
                                }
                                return response.json();
                            })
                            .then(data => {
                                console.log("予約削除成功:", data);
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
        });
    </script>

    <style>
        /* オーバーレイ（背景を暗くする） */
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

        /* モーダル本体 */
        .modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            text-align: center;
            width: 350px;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
            transform: translate(-50%, -45%);
        }

        /* モーダルがアクティブな時の表示 */
        .modal.active,
        .modal-overlay.active {
            display: block;
            opacity: 1;
        }

        .modal.active {
            transform: translate(-50%, -50%);
        }

        /* タイトル */
        .modal-title {
            font-size: 18px;
            margin-bottom: 15px;
            font-weight: bold;
        }

        /* ラベル */
        .modal-label {
            font-size: 14px;
            display: block;
            margin-bottom: 5px;
        }

        /* セレクトボックス */
        .modal-select {
            width: 100%;
            padding: 10px;
            font-size: 14px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        /* ボタンのスタイル */
        .modal-actions {
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }

        .btn {
            padding: 10px 15px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            border-radius: 5px;
            transition: 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #0056b3, #004494);
        }

        .btn-secondary {
            background: #ddd;
            color: #333;
        }

        .btn-secondary:hover {
            background: #bbb;
        }
    </style>
@endsection
