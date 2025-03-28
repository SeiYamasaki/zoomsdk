<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MeetingController extends Controller
{
    public function showJoinPage($meetingId)
    {
        return view('meetings.join', compact('meetingId'));
    }
}
