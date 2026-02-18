<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Camping;
use App\Models\Location;
use App\Models\Booking;

class CampingController extends Controller
{
    /**
     * @OA\Get(
     *     path="/campings",
     *     tags={"Campings"},
     *     summary="Get all campings with filters",
     *     description="Get paginated list of campings with optional search and price filters",
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by camping name or description",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="min_price",
     *         in="query",
     *         description="Minimum price per night",
     *         required=false,
     *         @OA\Schema(type="number")
     *     ),
     *     @OA\Parameter(
     *         name="max_price",
     *         in="query",
     *         description="Maximum price per night",
     *         required=false,
     *         @OA\Schema(type="number")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="last_page", type="integer"),
     *             @OA\Property(property="total", type="integer")
     *         )
     *     )
     * )
     */
    public function getCampings(Request $request)
    {
        // lekérés lapozzással 2/ oldalanként (teszt)
        // http://127.0.0.1:8000/api/campings?search=asd&page=2
        $camping = Camping::with(['photos', 'location', 'tags']);

        $camping->when($request->search, function ($query, $search) {
            $search = mb_strtolower($search);

            $query->whereRaw('LOWER(camping_name) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(description) LIKE ?', ["%{$search}%"]);
        });
        //http://127.0.0.1:8000/api/campings?min_price=1500&max_price=4000
        $camping->when($request->min_price || $request->max_price, function ($full) use ($request) {
            $full->whereHas('spots', function ($price) use ($request) {
                if ($request->min_price) {
                    $price->where('price_per_night', '>=', $request->min_price);
                }
                if ($request->max_price) {
                    $price->where('price_per_night', '<=', $request->max_price);
                }
            });
        });

        $campings = $camping->paginate(12); // 12 kemping oldalanként
        
        // árak és értékelések hozzáadása minden kempinghez
        $campings->getCollection()->transform(function ($camp) {
            $camp->min_price = $camp->min_price;
            $camp->max_price = $camp->max_price;
            $camp->average_rating = $camp->getAverageRating();
            $camp->reviews_count = $camp->getReviewsCount();
            return $camp;
        });
        
        return response()->json($campings);
    }

    /**
     * @OA\Get(
     *     path="/campings/{id}",
     *     tags={"Campings"},
     *     summary="Get camping details",
     *     description="Get detailed information about a specific camping",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Camping details",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(response=404, description="Camping not found")
     * )
     */
    public function show($id)
    {
        // Kemping megkeresése az összes kapcsolatával
        $camping = Camping::with([
            'photos', 
            'location', 
            'tags', 
            'spots', 
            'user',
            'entranceGates'
        ])->find($id);

        // error
        if (!$camping) {
            return response()->json([
                'message' => 'Kemping nem található'
            ], 404);
        }

        // árak és értékelések hozzáadása
        $camping->min_price = $camping->min_price;
        $camping->max_price = $camping->max_price;
        $camping->average_rating = $camping->getAverageRating();
        $camping->reviews_count = $camping->getReviewsCount();

        return response()->json($camping, 200);
    }

    /**
     * @OA\Post(
     *     path="/campings",
     *     tags={"Campings"},
     *     summary="Create new camping",
     *     description="Create a new camping (partner role required)",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"camping_name","owner_first_name","owner_last_name","description","city","zip_code","street_address"},
     *             @OA\Property(property="camping_name", type="string"),
     *             @OA\Property(property="owner_first_name", type="string"),
     *             @OA\Property(property="owner_last_name", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="company_name", type="string"),
     *             @OA\Property(property="tax_id", type="string"),
     *             @OA\Property(property="billing_address", type="string"),
     *             @OA\Property(property="city", type="string"),
     *             @OA\Property(property="zip_code", type="string"),
     *             @OA\Property(property="street_address", type="string"),
     *             @OA\Property(property="latitude", type="number"),
     *             @OA\Property(property="longitude", type="number")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Camping created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="camping", type="object")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Partner role required")
     * )
     */
    public function store(Request $request)
    {
        
        if (!$request->user()->role) {
            return response()->json([
                'message' => 'Nincs jogosultságod kemping létrehozásához. Először váltsd partner státuszra a fiókod!'
            ], 403);
        }

        
        $fields = $request->validate([
            'camping_name' => 'required|string|max:150',
            'owner_first_name' => 'required|string|max:100',
            'owner_last_name' => 'required|string|max:100',
            'description' => 'required|string',
            'company_name' => 'nullable|string|max:150',
            'tax_id' => 'nullable|string|max:50',
            'billing_address' => 'nullable|string|max:200',
            
            'city' => 'required|string|max:100',
            'zip_code' => 'required|string|max:10',
            'street_address' => 'required|string|max:200',
            'latitude' => 'nullable|numeric', // -> google api
            'longitude' => 'nullable|numeric',// -> google api
        ]);

        
        $location = Location::create([
            'city' => $fields['city'],
            'zip_code' => $fields['zip_code'],
            'street_address' => $fields['street_address'],
            'latitude' => $fields['latitude'] ?? null,
            'longitude' => $fields['longitude'] ?? null, // google api majd a jövöbeli kerséshez
        ]);


        $camping = Camping::create([
            'user_id' => $request->user()->id,
            'location_id' => $location->id,
            'camping_name' => $fields['camping_name'],
            'owner_first_name' => $fields['owner_first_name'],
            'owner_last_name' => $fields['owner_last_name'],
            'description' => $fields['description'],
            'company_name' => $fields['company_name'] ?? null,
            'tax_id' => $fields['tax_id'] ?? null,
            'billing_address' => $fields['billing_address'] ?? null,
        ]);

        
        $camping->load(['photos', 'location', 'tags', 'spots']);

        return response()->json([
            'message' => 'Kemping sikeresen létrehozva!',
            'camping' => $camping
        ], 201);
    }

    // Kemping módosítása 
    public function update(Request $request, $id)
    {
        
        $camping = Camping::find($id);

        if (!$camping) {
            return response()->json([
                'message' => 'Kemping nem található'
            ], 404);
        }

        // csak owner 
        if ($camping->user_id != $request->user()->id) {
            return response()->json([
                'message' => 'Nincs jogosultságod ennek a kempingnek a módosításához!',
                'debug' => [
                    'camping_user_id' => $camping->user_id,
                    'camping_user_id_type' => gettype($camping->user_id),
                    'current_user_id' => $request->user()->id,
                    'current_user_id_type' => gettype($request->user()->id)
                ]
            ], 403);
        }

        
        $fields = $request->validate([
            'camping_name' => 'sometimes|string|max:150',
            'owner_first_name' => 'sometimes|string|max:100',
            'owner_last_name' => 'sometimes|string|max:100',
            'description' => 'sometimes|string',
            'company_name' => 'nullable|string|max:150',
            'tax_id' => 'nullable|string|max:50',
            'billing_address' => 'nullable|string|max:200',
            'city' => 'sometimes|string|max:100',
            'zip_code' => 'sometimes|string|max:10',
            'street_address' => 'sometimes|string|max:200',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        // isset megnézése 
        if (isset($fields['city']) || isset($fields['zip_code']) || isset($fields['street_address'])) {
            $camping->location()->update([
                'city' => $fields['city'] ?? $camping->location->city,
                'zip_code' => $fields['zip_code'] ?? $camping->location->zip_code,
                'street_address' => $fields['street_address'] ?? $camping->location->street_address,
                'latitude' => $fields['latitude'] ?? $camping->location->latitude,
                'longitude' => $fields['longitude'] ?? $camping->location->longitude,
            ]);
        }

        
        $camping->update([
            'camping_name' => $fields['camping_name'] ?? $camping->camping_name,
            'owner_first_name' => $fields['owner_first_name'] ?? $camping->owner_first_name,
            'owner_last_name' => $fields['owner_last_name'] ?? $camping->owner_last_name,
            'description' => $fields['description'] ?? $camping->description,
            'company_name' => $fields['company_name'] ?? $camping->company_name,
            'tax_id' => $fields['tax_id'] ?? $camping->tax_id,
            'billing_address' => $fields['billing_address'] ?? $camping->billing_address,
        ]);

        
        $camping->load(['photos', 'location', 'tags', 'spots']);

        return response()->json([
            'message' => 'Kemping sikeresen frissítve!',
            'camping' => $camping
        ], 200);
    }

    // Kemping törlés
    public function destroy(Request $request, $id)
    {
        
        $camping = Camping::find($id);

        if (!$camping) {
            return response()->json([
                'message' => 'Kemping nem található'
            ], 404);
        }

      
        if ($camping->user_id != $request->user()->id) {
            return response()->json([
                'message' => 'Nincs jogosultságod ennek a kempingnek a törléséhez!'
            ], 403);
        }

      
        $camping->delete();

        return response()->json([
            'message' => 'Kemping sikeresen törölve!'
        ], 200);
    }

    // Kemping helyeinek listája
    public function getSpots($id)
    {
        
        $camping = Camping::find($id);

        if (!$camping) {
            return response()->json([
                'message' => 'Kemping nem található'
            ], 404);
        }

        
        $spots = $camping->spots()->get();

        return response()->json([
            'camping_id' => $camping->id,
            'camping_name' => $camping->camping_name,
            'spots' => $spots
        ], 200);
    }

    // Kemping foglaltsági naptára
    public function getAvailability(Request $request, $id)
    {
        
        $camping = Camping::find($id);

        if (!$camping) {
            return response()->json([
                'message' => 'Kemping nem található'
            ], 404);
        }

        // dátumok ellenőrzése
        $fields = $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        // dátumok átalakítása
        $startDate = \Carbon\Carbon::parse($fields['start_date'])->startOfDay();
        $endDate = \Carbon\Carbon::parse($fields['end_date'])->endOfDay();

        // az összes hely lekérése
        $spots = $camping->spots;

        // minden hely foglaltsági adatai
        $availability = [];
        
        foreach ($spots as $spot) {
            // foglalások keresése erre a helyre ebben az időszakban
            $bookings = Booking::where('camping_spot_id', $spot->spot_id)
                ->where('status', '!=', 'cancelled') // Törölt foglalások nem számítanak
                ->where(function ($query) use ($startDate, $endDate) {
                    // ha a foglalás beleesik a keresett időszakba
                    $query->where(function ($q) use ($startDate, $endDate) {
                        $q->where('arrival_date', '<=', $endDate)
                          ->where('departure_date', '>=', $startDate);
                    });
                })
                ->get(['arrival_date', 'departure_date', 'status'])
                ->map(function ($booking) {
                    return [
                        'arrival_date' => $booking->arrival_date->format('Y-m-d'),
                        'departure_date' => $booking->departure_date->format('Y-m-d'),
                        'status' => $booking->status,
                    ];
                });

            // van-e foglalás? Ha nincs, akkor szabad
            $isFree = $bookings->isEmpty();

            $availability[] = [
                'spot_id' => $spot->spot_id,
                'spot_number' => $spot->spot_number,
                'spot_type' => $spot->spot_type,
                'capacity' => $spot->capacity,
                'price_per_night' => $spot->price_per_night,
                'available' => $isFree, // boolean
                'bookings' => $bookings,
            ];
        }

        return response()->json([
            'camping_id' => $camping->id,
            'camping_name' => $camping->camping_name,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'availability' => $availability
        ], 200);
    }
}
