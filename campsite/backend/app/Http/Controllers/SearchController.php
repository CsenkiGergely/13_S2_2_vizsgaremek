<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Camping;
use App\Models\Location;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    // Helyszín javaslatok (autocomplete)
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

    // Keresés kempingek között név, leírás és helyszín alapján
    public function search(Request $request)
    {
        $query = $request->input('q');
        $location = $request->input('location');

        // Helyszín megadása kötelező (q vagy location)
        if (!$query && !$location) {
            return response()->json([
                'data' => [],
                'total' => 0,
                'last_page' => 1,
                'current_page' => 1,
                'per_page' => 9,
            ]);
        }

        $builder = Camping::with(['photos', 'location', 'tags', 'spots'])
            ->whereHas('spots');

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

        $page = $request->get('page', 1);
        $perPage = 9;
        $offset = ($page - 1) * $perPage;

        $paginatedItems = $results->slice($offset, $perPage)->values();

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedItems,
            $results->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()->json($paginator);
    }

    // Az összes kemping globális min/max ára (csak spot-tal rendelkezőknél)
    public function prices()
    {
        $row = DB::table('camping_spots')
            ->join('campings', 'camping_spots.camping_id', '=', 'campings.id')
            ->select(
                DB::raw('MIN(camping_spots.price_per_night) as min_price'),
                DB::raw('MAX(camping_spots.price_per_night) as max_price')
            )
            ->first();

        return response()->json([
            'min_price' => (int) ($row->min_price ?? 0),
            'max_price' => (int) ($row->max_price ?? 50000),
        ]);
    }

    // Az összes tag visszaadása a hozzájuk tartozó kemping-számmal
    public function tags()
    {
        $tags = DB::table('camping_tags')
            ->join('campings', 'camping_tags.camping_id', '=', 'campings.id')
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('camping_spots')
                  ->whereColumn('camping_spots.camping_id', 'campings.id');
            })
            ->select('camping_tags.tag as name', DB::raw('COUNT(*) as count'))
            ->groupBy('camping_tags.tag')
            ->orderByDesc('count')
            ->get();

        return response()->json($tags);
    }
}
