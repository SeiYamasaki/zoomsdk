<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Http\Controllers\Controller; // 追加

class BookingApiController extends Controller
{
    public function index()
    {
        return response()->json(Booking::all(), 200, [], JSON_UNESCAPED_UNICODE);
    }
}
