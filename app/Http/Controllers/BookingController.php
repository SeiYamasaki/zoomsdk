<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;

class BookingController extends Controller
{
    /**
     * カレンダー表示
     */
    public function index()
    {
        return view('calendar');
    }

    /**
     * 予約一覧表示
     */
    public function list()
    {
        $bookings = Booking::orderBy('start', 'desc')->get();
        return view('bookings.list', compact('bookings'));
    }
}
