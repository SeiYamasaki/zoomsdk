<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MeetingParticipant extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'booking_id',
        'name',
        'email',
        'access_token',
        'email_sent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_sent' => 'boolean',
        'registered_at' => 'datetime',
    ];

    /**
     * 参加者が属する予約
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * アクセストークンを生成
     *
     * @return string
     */
    public static function generateAccessToken()
    {
        return Str::random(64);
    }
}
