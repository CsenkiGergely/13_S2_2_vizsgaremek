<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Camping;

class SearchController extends Controller
{
    /**
     * Keresés kempingek között név, leírás és helyszín alapján
     */
    public function search(Request $request)
    {
        $query = $request->input('q');
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
        if (!$query) {
            return response()->json([]);
        }

        $results = Camping::with(['location', 'photos', 'tags'])
            ->where('camping_name', 'ILIKE', "%{$query}%")
            ->orWhere('description', 'ILIKE', "%{$query}%")
            ->orWhereHas('location', function ($q) use ($query) {
                $q->where('city', 'ILIKE', "%{$query}%");
            })
            ->get()
            ->map(function ($camping) {
                return [
                    'id' => $camping->id,
                    'name' => $camping->camping_name,
                    'description' => $camping->description,
                    'location' => $camping->location?->city,
                    'image' => $camping->photos->first()?->photo_url,
                    'tags' => $camping->tags->pluck('tag'),
                ];
            });


        return response()->json($results);
    }
}
