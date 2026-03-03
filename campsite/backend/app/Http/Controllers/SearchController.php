<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Camping;
use App\Models\Location;

class SearchController extends Controller
{
    /**
     * Helyszín javaslatok (autocomplete)
     * GET /api/locations/suggest?q=bal
     */
    public function suggest(Request $request)
    {
        $q = $request->input('q', '');

        if (mb_strlen($q) < 1) {
            return response()->json([]);
        }

        $search = mb_strtolower($q);

        // Városok keresése
        $cities = Location::whereRaw('LOWER(city) LIKE ?', ["%{$search}%"])
            ->select('city')
            ->distinct()
            ->limit(5)
            ->pluck('city')
            ->map(function ($city) {
                return ['type' => 'location', 'label' => $city];
            });

        // Kemping nevek keresése
        $campings = Camping::whereRaw('LOWER(camping_name) LIKE ?', ["%{$search}%"])
            ->select('id', 'camping_name')
            ->limit(5)
            ->get()
            ->map(function ($camp) {
                return ['type' => 'camping', 'label' => $camp->camping_name, 'id' => $camp->id];
            });

        // Egyesítés, max 8 találat
        $results = $cities->concat($campings)->take(8)->values();

        return response()->json($results);
    }

    /**
     * Keresés kempingek között név, leírás és helyszín alapján
     * GET /api/search?q=...&location=...&min_price=...&max_price=...&min_rating=...&tags=...
     */
    public function search(Request $request)
    {
        $query = $request->input('q');
        $location = $request->input('location');

        $builder = Camping::with(['photos', 'location', 'tags', 'spots']);

        // Szöveges keresés (név, leírás, helyszín)
        if ($query) {
            $search = mb_strtolower($query);
            $builder->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(camping_name) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(description) LIKE ?', ["%{$search}%"])
                  ->orWhereHas('location', function ($locQ) use ($search) {
                      $locQ->whereRaw('LOWER(city) LIKE ?', ["%{$search}%"])
                           ->orWhereRaw('LOWER(street_address) LIKE ?', ["%{$search}%"]);
                  });
            });
        }

        // Helyszín szűrő (külön, ha megadták)
        if ($location && !$query) {
            $loc = mb_strtolower($location);
            $builder->whereHas('location', function ($q) use ($loc) {
                $q->whereRaw('LOWER(city) LIKE ?', ["%{$loc}%"])
                  ->orWhereRaw('LOWER(street_address) LIKE ?', ["%{$loc}%"]);
            });
        }

        // Ár szűrés
        if ($request->min_price || $request->max_price) {
            $builder->whereHas('spots', function ($q) use ($request) {
                if ($request->min_price) {
                    $q->where('price_per_night', '>=', $request->min_price);
                }
                if ($request->max_price) {
                    $q->where('price_per_night', '<=', $request->max_price);
                }
            });
        }

        // Tag szűrés (vesszővel elválasztva)
        if ($request->tags) {
            $tags = array_map('trim', explode(',', $request->tags));
            $builder->whereHas('tags', function ($q) use ($tags) {
                $q->whereIn('tag', $tags);
            });
        }

        $results = $builder->get();

        // Árak és értékelések hozzáadása
        $results->transform(function ($camp) {
            $camp->min_price = $camp->min_price;
            $camp->max_price = $camp->max_price;
            $camp->average_rating = $camp->getAverageRating();
            $camp->reviews_count = $camp->getReviewsCount();
            $camp->available_capacity = $camp->spots->sum('capacity');
            $camp->available_spots_count = $camp->spots->count();
            return $camp;
        });

        // Minimum értékelés szűrés (a transform után)
        if ($request->min_rating) {
            $minRating = (float) $request->min_rating;
            $results = $results->filter(function ($camp) use ($minRating) {
                return $camp->average_rating >= $minRating;
            })->values();
        }

        return response()->json($results);
    }
}
