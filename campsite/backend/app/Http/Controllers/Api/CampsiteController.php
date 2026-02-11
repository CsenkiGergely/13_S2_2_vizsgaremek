<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Camping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CampsiteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Camping::with(['location', 'tags', 'photos', 'spots']);

        // Szöveges keresés (camping_name, city)
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('camping_name', 'ILIKE', "%{$search}%")
                    ->orWhereHas('location', function ($locQuery) use ($search) {
                        $locQuery->where('city', 'ILIKE', "%{$search}%")
                            ->orWhere('street_address', 'ILIKE', "%{$search}%");
                    });
            });
        }

        // Ár szűrés (camping_spots alapján)
        if ($request->has('max_price')) {
            $query->whereHas('spots', function ($spotQuery) use ($request) {
                $spotQuery->where('price_per_night', '<=', $request->max_price);
            });
        }

        // Értékelés szűrés (comments átlag rating alapján)
        if ($request->has('min_rating')) {
            $minRating = $request->min_rating;
            $query->whereHas('comments', function ($commentQuery) {
                $commentQuery->whereNotNull('rating');
            });
        }

        // Services/tags szűrés (camping_tags táblából)
        if ($request->has('services')) {
            $services = $request->services;
            if (is_string($services)) {
                $services = explode(',', $services);
            }
            
            foreach ($services as $service) {
                $query->whereHas('tags', function ($tagQuery) use ($service) {
                    $tagQuery->where('tag', 'ILIKE', '%' . trim($service) . '%');
                });
            }
        }

        // Location types szűrés (ezt hozzá kellene adni a camping_tags-hez vagy külön táblába)
        // Egyelőre ezt kihagyjuk, mert nincs ilyen mező a jelenlegi struktúrában

        $campings = $query->get()->map(function ($camping) {
            return [
                'id' => $camping->id,
                'name' => $camping->camping_name,
                'location' => $camping->location ? $camping->location->city : null,
                'full_address' => $camping->location ? 
                    "{$camping->location->city}, {$camping->location->street_address}" : null,
                'rating' => $camping->getAverageRating(),
                'reviews' => $camping->getReviewsCount(),
                'price' => $camping->spots->min('price_per_night'),
                'max_price' => $camping->spots->max('price_per_night'),
                'image' => $camping->photos->first()?->photo_url,
                'images' => $camping->photos->pluck('photo_url'),
                'tags' => $camping->tags->pluck('tag'),
                'description' => $camping->description,
                'spots_available' => $camping->spots->where('is_available', true)->count(),
            ];
        });

        return response()->json($campings);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $camping = Camping::with(['location', 'tags', 'photos', 'spots', 'comments.user'])
            ->findOrFail($id);

        return response()->json([
            'id' => $camping->id,
            'name' => $camping->camping_name,
            'location' => $camping->location ? $camping->location->city : null,
            'full_address' => $camping->location ? 
                "{$camping->location->city}, {$camping->location->zip_code}, {$camping->location->street_address}" : null,
            'coordinates' => [
                'lat' => $camping->location?->latitude,
                'lng' => $camping->location?->longitude,
            ],
            'rating' => $camping->getAverageRating(),
            'reviews' => $camping->getReviewsCount(),
            'price' => $camping->spots->min('price_per_night'),
            'max_price' => $camping->spots->max('price_per_night'),
            'images' => $camping->photos->pluck('photo_url'),
            'tags' => $camping->tags->pluck('tag'),
            'description' => $camping->description,
            'owner' => [
                'first_name' => $camping->owner_first_name,
                'last_name' => $camping->owner_last_name,
                'company_name' => $camping->company_name,
            ],
            'spots' => $camping->spots->map(function ($spot) {
                return [
                    'id' => $spot->spot_id,
                    'name' => $spot->name,
                    'type' => $spot->type,
                    'capacity' => $spot->capacity,
                    'price_per_night' => $spot->price_per_night,
                    'is_available' => $spot->is_available,
                    'description' => $spot->description,
                ];
            }),
            'comments' => $camping->comments->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'user' => $comment->user->name ?? 'Ismeretlen',
                    'rating' => $comment->rating,
                    'comment' => $comment->comment,
                    'date' => $comment->upload_date,
                ];
            }),
        ]);
    }
}
