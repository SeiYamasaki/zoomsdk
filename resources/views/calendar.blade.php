@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>予約カレンダー</h2>
        <div id="calendar"></div>
    </div>

    <!-- モーダル -->
    <div id="bookingModal"
        style="
    display: none; 
    position: fixed; 
    top: 50%; 
    left: 50%; 
    transform: translate(-50%, -50%);
    background: white; 
    padding: 20px; 
    border-radius: 5px; 
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    z-index: 1000; /* モーダルを前面に */
">
        <h3>予約を追加</h3>
        <label>時間:</label>
        <select id="timeSlot">
            @for ($hour = 10; $hour < 24; $hour++)
                <option value="{{ sprintf('%02d:00:00', $hour) }}">{{ sprintf('%02d:00', $hour) }} -
                    {{ sprintf('%02d:30', $hour) }}</option>
                <option value="{{ sprintf('%02d:30:00', $hour) }}">{{ sprintf('%02d:30', $hour) }} -
                    {{ sprintf('%02d:00', $hour + 1) }}</option>
            @endfor
        </select>

        <button id="saveBooking">予約する</button>
        <button id="closeModal">キャンセル</button>
    </div>

    <!-- 背景の黒いオーバーレイ -->
    <div id="modalOverlay"
        style="
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999; /* モーダルの後ろに配置 */
">
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

                // 日付をクリックしたとき
                dateClick: function(info) {
                    selectedDate = info.dateStr;
                    document.getElementById('bookingModal').style.display = 'block';
                    document.getElementById('modalOverlay').style.display = 'block';
                }
            });

            calendar.render();

            // モーダルの閉じるボタン
            document.getElementById('closeModal').addEventListener('click', function() {
                document.getElementById('bookingModal').style.display = 'none';
                document.getElementById('modalOverlay').style.display = 'none';
            });

            // 予約ボタンの処理
            document.getElementById('saveBooking').addEventListener('click', function() {
                if (!selectedDate) {
                    alert("日付が選択されていません");
                    return;
                }

                var selectedTime = document.getElementById('timeSlot').value;
                var startTime = selectedDate + 'T' + selectedTime;
                var endTime = new Date(new Date(startTime).getTime() + (30 * 60 * 1000))
                    .toISOString(); // 30分後

                console.log('送信するデータ:', {
                    title: "予約",
                    start: startTime,
                    end: endTime
                });

                fetch('/bookings', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            title: "予約",
                            start: startTime,
                            end: endTime
                        })
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(errorData => {
                                throw new Error(errorData.error || "予約の登録に失敗しました");
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log("予約成功:", data);
                        calendar.refetchEvents(); // カレンダーを更新
                        document.getElementById('bookingModal').style.display = 'none';
                        document.getElementById('modalOverlay').style.display = 'none';
                    })
                    .catch(error => {
                        console.error("エラー:", error);
                        alert("予約の登録に失敗しました。エラー: " + error.message);
                    });
            });
        });
    </script>
@endsection
