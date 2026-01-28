<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Camping;
use App\Models\Booking;

class BookingSearchController extends Controller
{
    // Keresés
    public function search(Request $request)
    {
        $request->validate([
            'location' => 'nullable|string',
            'arrival_date' => 'required|date|after_or_equal:today',
            'departure_date' => 'required|date|after:arrival_date',
            'guests' => 'required|integer|min:1'
        ]);

        // http://127.0.0.1:8000/api/booking/search?location=Siófok&arrival_date=2026-06-01&departure_date=2026-06-05&guests=14&page=1
        
        // Elérhető helyek ellenőrzése a cemnping foglalásai alapján
        $query = Camping::with(['photos', 'location', 'tags', 'spots' => function ($q) use ($request) {
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

        // Város megye alapú szűrés 
        if ($request->location) {
            $location = '%' . mb_strtolower($request->location) . '%';
            $query->whereHas('location', function ($q) use ($location) {
                $q->whereRaw('LOWER(city) LIKE ?', [$location])
                  ->orWhereRaw('LOWER(county) LIKE ?', [$location]);
            });
        }

         // Elérhető helyek ellenőrzése a cemnping kapacitásához mérten 
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


        $campings = $query->get();

        // Ellenőrizzük az összkapacitás elég-e 
        $filteredCampings = $campings->filter(function ($camping) use ($request) {
            $totalCapacity = $camping->spots->sum('capacity');
            return $totalCapacity >= $request->guests;
        });

        // Elérhető kapacitás helyek száma árak és értékelések
        $filteredCampings->each(function ($camping) {
            $camping->available_capacity = $camping->spots->sum('capacity');
            $camping->available_spots_count = $camping->spots->count();
            
            // Minimum és maximum árak számítása az elérhető helyekből
            $camping->min_price = $camping->spots->min('price_per_night');
            $camping->max_price = $camping->spots->max('price_per_night');
            
            // Átlag értékelés és értékelések száma
            $camping->average_rating = $camping->getAverageRating();
            $camping->reviews_count = $camping->getReviewsCount();
        });

        // 10 camping / oldal 
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