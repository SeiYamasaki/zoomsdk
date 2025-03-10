<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'start',
        'end',
        'host_user_id',
        'participant_email',
        'zoom_meeting_id',
        'zoom_meeting_url',
        'zoom_meeting_password',
        'waiting_room',
        'max_participants'
    ];

    /**
     * この予約に参加登録したユーザー
     */
    public function participants()
    {
        return $this->hasMany(MeetingParticipant::class);
    }
}
