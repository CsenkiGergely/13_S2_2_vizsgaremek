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
     * Kempingek listázása szűrőkkel
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

        $campings = $camping->paginate(2); // 2 egyszerre
        
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
     * Kemping részleteinek lekérdezése
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
     * Új kemping létrehozása
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
                'message' => 'Nincs jogosultságod ennek a kempingnek a módosításához!'
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

    /**
     * GeoJSON térkép lekérése egy kempinghez
     */
    public function getGeojson($id)
    {
        $camping = Camping::find($id);

        if (!$camping) {
            return response()->json(['message' => 'Kemping nem található'], 404);
        }

        if (!$camping->geojson) {
            return response()->json(['message' => 'Ehhez a kempinghez nincs térkép feltöltve'], 404);
        }

        return response()->json([
            'camping_id' => $camping->id,
            'camping_name' => $camping->name,
            'geojson' => $camping->geojson
        ], 200);
    }

    /**
     * GeoJSON térkép feltöltése kempinghez (csak tulajdonos)
     * A tulajdonos geojson.io-n megrajzolja a térképet és feltölti
     */
    public function uploadGeojson(Request $request, $id)
    {
        $camping = Camping::find($id);

        if (!$camping) {
            return response()->json(['message' => 'Kemping nem található'], 404);
        }

        if ($camping->user_id != $request->user()->id) {
            return response()->json(['message' => 'Nincs jogosultságod!'], 403);
        }

        $request->validate([
            'geojson' => 'required|file|mimes:json,geojson|max:2048',
        ]);

        $file = $request->file('geojson');

        // Csak .geojson kiterjesztés engedélyezett
        if ($file->getClientOriginalExtension() !== 'geojson') {
            return response()->json(['message' => 'Csak .geojson fájl tölthető fel!'], 422);
        }

        // Fájl tartalom beolvasása
        $content = file_get_contents($file->getRealPath());
        $geojson = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['message' => 'Érvénytelen JSON formátum a fájlban!'], 422);
        }

        // Ellenőrzés: FeatureCollection típusú legyen
        if (!isset($geojson['type']) || $geojson['type'] !== 'FeatureCollection') {
            return response()->json(['message' => 'A GeoJSON-nak FeatureCollection típusúnak kell lennie!'], 422);
        }

        $camping->geojson = $geojson;
        $camping->save();

        return response()->json([
            'message' => 'Térkép sikeresen feltöltve!',
            'camping_id' => $camping->id,
            'camping_name' => $camping->camping_name,
            'geojson' => $camping->geojson
        ], 200);
    }

    /**
     * GeoJSON térkép törlése (csak tulajdonos)
     */
    public function deleteGeojson(Request $request, $id)
    {
        $camping = Camping::find($id);

        if (!$camping) {
            return response()->json(['message' => 'Kemping nem található'], 404);
        }

        if ($camping->user_id != $request->user()->id) {
            return response()->json(['message' => 'Nincs jogosultságod!'], 403);
        }

        $camping->geojson = null;
        $camping->save();

        return response()->json(['message' => 'Térkép törölve!'], 200);
    }
}
