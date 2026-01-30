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
        $request->validate([
            'location' => 'nullable|string',
            'arrival_date' => 'required|date|after_or_equal:today',
            'departure_date' => 'required|date|after:arrival_date',
            'guests' => 'required|integer|min:1'
        ]);

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

        if ($request->location) {
            $location = '%' . mb_strtolower($request->location) . '%';
            $query->whereHas('location', function ($q) use ($location) {
                $q->whereRaw('LOWER(city) LIKE ?', [$location]);
            });
        }

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

        $filteredCampings = $campings->filter(function ($camping) use ($request) {
            $totalCapacity = $camping->spots->sum('capacity');
            return $totalCapacity >= $request->guests;
        });

        $filteredCampings->each(function ($camping) {
            $camping->available_capacity = $camping->spots->sum('capacity');
            $camping->available_spots_count = $camping->spots->count();
            
            $camping->min_price = $camping->spots->min('price_per_night');
            $camping->max_price = $camping->spots->max('price_per_night');
            
            $camping->average_rating = $camping->getAverageRating();
            $camping->reviews_count = $camping->getReviewsCount();
        });

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