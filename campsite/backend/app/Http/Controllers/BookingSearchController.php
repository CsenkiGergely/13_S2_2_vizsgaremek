<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Camping;
use App\Models\Booking;

class BookingSearchController extends Controller
{
    public function search(Request $request)
    {
        // Validáció
        $request->validate([
            'location' => 'nullable|string',
            'arrival_date' => 'required|date|after_or_equal:today',
            'departure_date' => 'required|date|after:arrival_date',
            'guests' => 'required|integer|min:1'
        ]);

        $query = Camping::with(['photos', 'location', 'tags', 'spots' => function ($q) use ($request) {
            // Csak azokat a spotokat töltjük be, amik szabadok
            $q->where('is_available', true)
              ->whereDoesntHave('bookings', function ($bookingQuery) use ($request) {
                  $bookingQuery->where(function ($dateQuery) use ($request) {
                      $dateQuery->whereBetween('arrival_date', [$request->arrival_date, $request->departure_date])
                                ->orWhereBetween('departure_date', [$request->arrival_date, $request->departure_date])
                                ->orWhere(function ($overlapQuery) use ($request) {
                                    $overlapQuery->where('arrival_date', '<=', $request->arrival_date)
                                                 ->where('departure_date', '>=', $request->departure_date);
                                });
                  });
              });
        }]);

        // Hely szerinti szűrés (város vagy megye)
        if ($request->location) {
            $location = '%' . mb_strtolower($request->location) . '%';
            $query->whereHas('location', function ($q) use ($location) {
                $q->whereRaw('LOWER(city) LIKE ?', [$location])
                  ->orWhereRaw('LOWER(county) LIKE ?', [$location]);
            });
        }

        // Olyan kempingek, ahol van elég összkapacitás a szabad helyeken
        $query->whereHas('spots', function ($q) use ($request) {
            $q->where('is_available', true)
              ->whereDoesntHave('bookings', function ($bookingQuery) use ($request) {
                  $bookingQuery->where(function ($dateQuery) use ($request) {
                      $dateQuery->whereBetween('arrival_date', [$request->arrival_date, $request->departure_date])
                                ->orWhereBetween('departure_date', [$request->arrival_date, $request->departure_date])
                                ->orWhere(function ($overlapQuery) use ($request) {
                                    $overlapQuery->where('arrival_date', '<=', $request->arrival_date)
                                                 ->where('departure_date', '>=', $request->departure_date);
                                });
                  });
              });
        });

        // Lekérjük a kempingeket
        $campings = $query->get();

        // Szűrjük azokat, ahol az összes szabad hely kapacitása >= kért létszám
        $filteredCampings = $campings->filter(function ($camping) use ($request) {
            $totalCapacity = $camping->spots->sum('capacity');
            return $totalCapacity >= $request->guests;
        });

        // Hozzáadjuk az available_capacity mezőt minden kempinghez
        $filteredCampings->each(function ($camping) {
            $camping->available_capacity = $camping->spots->sum('capacity');
            $camping->available_spots_count = $camping->spots->count();
        });

        // Lapozás manuálisan
        $page = $request->get('page', 1);
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        
        $paginatedItems = $filteredCampings->slice($offset, $perPage)->values();
        
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedItems,
            $filteredCampings->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()->json($paginator);
    }
}
