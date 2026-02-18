<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Camping;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        error_log("bejutott");
        $query = $request->input('q');
<<<<<<< Updated upstream
        error_log($query);
        $results = Item::where('title', 'like', "%{$query}%")
                       ->orWhere('description', 'like', "%{$query}%")
                       ->get();
=======
        $location = $request->input('location');
        $checkIn = $request->input('checkIn');
        $checkOut = $request->input('checkOut');

        $results = Camping::with(['photos', 'location', 'tags', 'spots'])
            ->when($query, function ($q) use ($query) {
                $q->where('camping_name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->when($location, function ($q) use ($location) {
                $q->whereHas('location', function ($locQuery) use ($location) {
                    $locQuery->where('city', 'like', "%{$location}%")
                             ->orWhere('street_address', 'like', "%{$location}%");
                });
            })
            ->get();

        // Árak és értékelések hozzáadása
        $results->transform(function ($camp) {
            $camp->min_price = $camp->min_price;
            $camp->max_price = $camp->max_price;
            $camp->average_rating = $camp->getAverageRating();
            $camp->reviews_count = $camp->getReviewsCount();
            return $camp;
        });
>>>>>>> Stashed changes

        return response()->json($results);
    }
}
