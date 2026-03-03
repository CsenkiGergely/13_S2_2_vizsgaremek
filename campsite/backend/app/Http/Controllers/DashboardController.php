<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Camping;
use App\Models\CampingSpot;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function getOwnerDashboard(Request $request)
    {
        $user = $request->user();

        // owner kempingjeinek id-jai
        $myCampingIds = Camping::where('user_id', $user->id)->pluck('id');

        // owner kempingjeihez tartozo spot id-k
        $mySpotIds = CampingSpot::whereIn('camping_id', $myCampingIds)->pluck('spot_id');

        // Aktuális hónap és előző hónap
        $now = Carbon::now();
        $currentMonth = $now->month;
        $currentYear = $now->year;
        $prev = $now->copy()->subMonth();
        $prevMonth = $prev->month;
        $prevYear = $prev->year;

        // havi foglalasok szama - aktualis honap
        $totalBookings = Booking::whereIn('camping_spot_id', $mySpotIds)
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();

        // elozo havi foglalasok szama
        $previousTotalBookings = Booking::whereIn('camping_spot_id', $mySpotIds)
            ->whereMonth('created_at', $prevMonth)
            ->whereYear('created_at', $prevYear)
            ->count();

        // aktiv vendegek - csak a sajat kempingek
        $activeGuests = Booking::whereIn('camping_spot_id', $mySpotIds)
            ->where('status', 'checked_in')->count();

        // foglalt helyek - aktiv foglalasok alapjan (confirmed vagy checked_in, ma az idoszakban)
        $today = Carbon::today();
        $bookedSpots = Booking::whereIn('camping_id', $myCampingIds)
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->where('arrival_date', '<=', $today)
            ->where('departure_date', '>', $today)
            ->selectRaw('COUNT(DISTINCT camping_spot_id) as cnt')
            ->value('cnt');
        $totalSpots = CampingSpot::whereIn('camping_id', $myCampingIds)->count();

        // havi bevetel - csak a sajat kempingek foglalasai
        $monthlyRevenue = Booking::with('campingSpot')
            ->whereIn('camping_spot_id', $mySpotIds)
            ->whereIn('status', ['confirmed', 'checked_in', 'finished'])
            ->whereMonth('departure_date', $currentMonth)
            ->whereYear('departure_date', $currentYear)
            ->get()
            ->sum(fn ($booking) => $this->calculateBookingPrice($booking));

        // elozo honap bevetel
        $previousMonthlyRevenue = Booking::with('campingSpot')
            ->whereIn('camping_spot_id', $mySpotIds)
            ->whereIn('status', ['confirmed', 'checked_in', 'finished'])
            ->whereMonth('departure_date', $prevMonth)
            ->whereYear('departure_date', $prevYear)
            ->get()
            ->sum(fn ($booking) => $this->calculateBookingPrice($booking));

        // atlagos foglalasi ertekek - sajat kempingekre
        $currentMonthBookings = Booking::with('campingSpot')
            ->whereIn('camping_spot_id', $mySpotIds)
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
            ->whereIn('camping_spot_id', $mySpotIds)
            ->whereIn('status', ['confirmed', 'checked_in', 'finished'])
            ->whereMonth('departure_date', $prevMonth)
            ->whereYear('departure_date', $prevYear)
            ->get();

        $previousAverageBookingValue = 0;
        if ($previousMonthBookings->count() > 0) {
            $totalPrev = $previousMonthBookings->sum(fn ($b) => $this->calculateBookingPrice($b));
            $previousAverageBookingValue = (int) round($totalPrev / $previousMonthBookings->count());
        }

        // legutobbi foglalasok - csak a sajat kempingek
        $recentBookings = Booking::with(['user', 'campingSpot'])
            ->whereIn('camping_spot_id', $mySpotIds)
            ->orderBy('created_at', 'desc')
            ->limit(10)
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
                    'price' => $this->calculateBookingPrice($booking),
                    'createdAt' => $booking->created_at->toIso8601String(),
                ];
            });

        // elozo honap - sajat kempingekre szurve
        $previousActiveGuests = Booking::whereIn('camping_spot_id', $mySpotIds)
            ->where('status', 'checked_in')
            ->whereMonth('created_at', $prevMonth)
            ->whereYear('created_at', $prevYear)
            ->count();

        $previousBookedSpots = Booking::whereIn('camping_spot_id', $mySpotIds)
            ->whereIn('status', ['confirmed', 'checked_in', 'finished'])
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