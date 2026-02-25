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

        // Összes foglalás száma (összes időre)
        $totalBookings = Booking::count();

        // Aktív vendégek (jelenleg bent)
        $activeGuests = Booking::where('status', 'checked_in')->count();

        // Foglalt helyek szám (aktuális állapot)
        $bookedSpots = CampingSpot::where('is_available', false)->count();
        $totalSpots = CampingSpot::count();

        // Aktuális hónap és előző hónap
        $now = Carbon::now();
        $currentMonth = $now->month;
        $currentYear = $now->year;
        $prev = $now->copy()->subMonth();
        $prevMonth = $prev->month;
        $prevYear = $prev->year;

        // Havi bevétel (aktuális hó: befejezett/confirmed/checked_in foglalások departure_date alapján)
        $monthlyRevenue = Booking::with('campingSpot')
            ->whereIn('status', ['confirmed', 'checked_in', 'finished'])
            ->whereMonth('departure_date', $currentMonth)
            ->whereYear('departure_date', $currentYear)
            ->get()
            ->sum(fn ($booking) => $this->calculateBookingPrice($booking));

        // Előző hónap bevétel (ugyanaz a logika)
        $previousMonthlyRevenue = Booking::with('campingSpot')
            ->whereIn('status', ['confirmed', 'checked_in', 'finished'])
            ->whereMonth('departure_date', $prevMonth)
            ->whereYear('departure_date', $prevYear)
            ->get()
            ->sum(fn ($booking) => $this->calculateBookingPrice($booking));

        // --- Átlagos foglalási értékek (backend számolja) ---
        $currentMonthBookings = Booking::with('campingSpot')
            ->whereIn('status', ['confirmed', 'checked_in', 'finished'])
            ->whereMonth('departure_date', $currentMonth)
            ->whereYear('departure_date', $currentYear)
            ->get();

        $averageBookingValue = 0;
        if ($currentMonthBookings->count() > 0) {
            $totalCurrent = $currentMonthBookings->sum(fn ($b) => $this->calculateBookingPrice($b));
            $averageBookingValue = (int) round($totalCurrent / $currentMonthBookings->count());
        }

        $previousMonthBookings = Booking::with('campingSpot')
            ->whereIn('status', ['confirmed', 'checked_in', 'finished'])
            ->whereMonth('departure_date', $prevMonth)
            ->whereYear('departure_date', $prevYear)
            ->get();

        $previousAverageBookingValue = 0;
        if ($previousMonthBookings->count() > 0) {
            $totalPrev = $previousMonthBookings->sum(fn ($b) => $this->calculateBookingPrice($b));
            $previousAverageBookingValue = (int) round($totalPrev / $previousMonthBookings->count());
        }

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

        // Előző hónap: összes foglalás (létrehozás szerint)
        $previousTotalBookings = Booking::whereMonth('created_at', $prevMonth)
            ->whereYear('created_at', $prevYear)
            ->count();

        // Előző hónap: aktív vendégek (checked_in státuszú foglalások a hónapban)
        $previousActiveGuests = Booking::where('status', 'checked_in')
            ->whereMonth('created_at', $prevMonth)
            ->whereYear('created_at', $prevYear)
            ->count();

        // Előző hónap: foglalt helyek számítása — egy egyszerű megközelítés: hány különböző spot volt lefoglalva az előző hónapban
        $previousBookedSpots = Booking::whereIn('status', ['confirmed', 'checked_in', 'finished'])
            ->whereMonth('departure_date', $prevMonth)
            ->whereYear('departure_date', $prevYear)
            ->distinct('camping_spot_id')
            ->count('camping_spot_id');

        $previousOccupancyPercentage = $totalSpots > 0 ? round(($previousBookedSpots / $totalSpots) * 100) : 0;

        return response()->json([
            'totalBookings' => $totalBookings,
            'previousTotalBookings' => $previousTotalBookings,
            'activeGuests' => $activeGuests,
            'previousActiveGuests' => $previousActiveGuests,
            'bookedSpots' => $bookedSpots,
            'previousBookedSpots' => $previousBookedSpots,
            'totalSpots' => $totalSpots,
            'occupancyPercentage' => $totalSpots > 0 ? round(($bookedSpots / $totalSpots) * 100) : 0,
            'previousOccupancyPercentage' => $previousOccupancyPercentage,
            'monthlyRevenue' => $monthlyRevenue,
            'previousMonthlyRevenue' => $previousMonthlyRevenue,
            'averageBookingValue' => $averageBookingValue,
            'previousAverageBookingValue' => $previousAverageBookingValue,
            'recentBookings' => $recentBookings
        ]);
    }

    private function calculateBookingPrice($booking)
    {
        $nights = (strtotime($booking->departure_date) - strtotime($booking->arrival_date)) / (60 * 60 * 24);
        return $nights > 0 ? $nights * ($booking->campingSpot->price_per_night ?? 0) : 0;
    }
}