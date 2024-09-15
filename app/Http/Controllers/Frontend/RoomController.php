<?php
namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\Booking;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index()
    {
        $rooms = Room::all();
        return view('frontend.rooms.index', compact('rooms'));
    }

    public function show($id)
    {
        $room = Room::findOrFail($id);
        return view('frontend.rooms.show', compact('room'));
    }

    public function getAvailability($roomId)
    {
        $room = Room::findOrFail($roomId);

        // Fetch bookings for the room
        $bookings = Booking::where('room_id', $roomId)
            ->get();

        // Determine unavailable dates
        $unavailableDates = [];
        foreach ($bookings as $booking) {
            $start = \Carbon\Carbon::parse($booking->check_in);
            $end = \Carbon\Carbon::parse($booking->check_out);

            while ($start->lte($end)) {
                $unavailableDates[] = $start->format('Y-m-d');
                $start->addDay();
            }
        }

        return response()->json(['unavailableDates' => $unavailableDates]);
    }


}
