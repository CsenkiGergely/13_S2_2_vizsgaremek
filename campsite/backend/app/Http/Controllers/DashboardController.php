<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\CampingSpot;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function getOwnerDashboard(Request $request)
    {
        $user = $request->user();

        // Összes foglalás száma
        $totalBookings = Booking::count();

        // Aktív vendégek (jelenleg bent)
        $activeGuests = Booking::where('status', 'checked_in')->count();

        // Foglalt helyek szám
        $bookedSpots = CampingSpot::where('is_available', false)->count();
        $totalSpots = CampingSpot::count();

        // Havi bevétel (aktuális hó befejezett foglalások)
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        
        $monthlyRevenue = Booking::with('campingSpot')
            ->whereIn('status', ['confirmed', 'checked_in', 'finished'])
            ->whereMonth('departure_date', $currentMonth)
            ->whereYear('departure_date', $currentYear)
            ->get()
            ->sum(fn ($booking) => $this->calculateBookingPrice($booking));

        // Legutóbbi foglalások
        $recentBookings = Booking::with(['user', 'campingSpot'])
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get()
            ->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'guestFirstName' => $booking->user->owner_first_name,
                    'guestLastName' => $booking->user->owner_last_name,
                    'spot' => $booking->campingSpot->name ?? 'N/A',
                    'checkIn' => $booking->arrival_date,
                    'checkOut' => $booking->departure_date,
                    'guests' => $booking->guests,
                    'status' => $booking->status,
                    'price' => $this->calculateBookingPrice($booking)
                ];
            });

        return response()->json([
            'totalBookings' => $totalBookings,
            'activeGuests' => $activeGuests,
            'bookedSpots' => $bookedSpots,
            'totalSpots' => $totalSpots,
            'occupancyPercentage' => $totalSpots > 0 ? round(($bookedSpots / $totalSpots) * 100) : 0,
            'monthlyRevenue' => $monthlyRevenue,
            'recentBookings' => $recentBookings
        ]);
    }

    private function calculateBookingPrice($booking)
    {
        $nights = (strtotime($booking->departure_date) - strtotime($booking->arrival_date)) / (60 * 60 * 24);
        return $nights > 0 ? $nights * ($booking->campingSpot->price_per_night ?? 0) : 0;
    }
}