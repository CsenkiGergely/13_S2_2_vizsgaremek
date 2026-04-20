<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CampingSpot;
use App\Models\Camping;

class CampingSpotController extends Controller
{
    // Egy kemping összes helyének lekérése (foglaltsági állapottal)
    public function index(Request $request, $campingId)
    {
        $camping = Camping::find($campingId);

        if (!$camping) {
            return response()->json(['message' => 'Kemping nem található'], 404);
        }

        $spots = CampingSpot::where('camping_id', $campingId)->get();

        // Ha van dátum szűrő, jelöljük melyik hely foglalt
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $spots->each(function ($spot) use ($startDate, $endDate) {
            if ($startDate && $endDate) {
                // Van-e aktív foglalás erre az időszakra
                $spot->is_booked = $spot->bookings()
                    ->where('status', '!=', 'cancelled')
                    ->where('arrival_date', '<', $endDate)
                    ->where('departure_date', '>', $startDate)
                    ->exists();
            } else {
                $spot->is_booked = false;
            }
        });

        return response()->json($spots, 200);
    }

    // Egy adott hely részletei
    public function show($campingId, $spotId)
    {
        $spot = CampingSpot::where('camping_id', $campingId)
            ->where('spot_id', $spotId)
            ->first();

        if (!$spot) {
            return response()->json(['message' => 'Kemping hely nem található'], 404);
        }

        return response()->json($spot, 200);
    }

    // Új kemping hely létrehozása
    public function store(Request $request, $campingId)
    {
        // Felhasználó ellenőrzése
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Nincs bejelentkezve'], 401);
        }

        // Kemping megkeresése
        $camping = Camping::find($campingId);

        if (!$camping) {
            return response()->json(['message' => 'Kemping nem található'], 404);
        }

        // csak a tulajdonos hozhat létre helyet
        if ($camping->user_id != $user->id) {
            return response()->json(['message' => 'Nincs jogosultságod!'], 403);
        }

        // Validálás
        $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|string|max:50',
            'capacity' => 'required|integer|min:1',
            'price_per_night' => 'required|numeric|min:0',
        ]);

        // Új hely létrehozása
        $spot = new CampingSpot();
        $spot->camping_id = $campingId;
        $spot->name = $request->name;
        $spot->type = $request->type;
        $spot->capacity = $request->capacity;
        $spot->price_per_night = $request->price_per_night;
        
        // Opcionális mezők -> késöbbi map miatt is
        if ($request->has('description')) {
            $spot->description = $request->description;
        }
        if ($request->has('row')) {
            $spot->row = $request->row;
        }
        if ($request->has('column')) {
            $spot->column = $request->column;
        }
        
        $spot->save();

        return response()->json([
            'message' => 'Kemping hely sikeresen létrehozva!',
            'spot' => $spot
        ], 201);
    }

    // Kemping hely módosítása 
    public function update(Request $request, $campingId, $spotId)
    {
        // Felhasználó ellenőrzése
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Nincs bejelentkezve'], 401);
        }

        // Kemping megkeresése
        $camping = Camping::find($campingId);

        if (!$camping) {
            return response()->json(['message' => 'Kemping nem található'], 404);
        }

        // csak a tulajdonos módosíthatja
        if ($camping->user_id != $user->id) {
            return response()->json(['message' => 'Nincs jogosultságod!'], 403);
        }

        // Hely megkeresése
        $spot = CampingSpot::where('camping_id', $campingId)
            ->where('spot_id', $spotId)
            ->first();

        if (!$spot) {
            return response()->json(['message' => 'Kemping hely nem található'], 404);
        }

        // Validálás minden mező opcionális ('sometimes'), de ha jön, típusa ellenőrzött
        $validated = $request->validate([
            'name'            => 'sometimes|string|max:100',
            'type'            => 'sometimes|string|max:50',
            'capacity'        => 'sometimes|integer|min:1',
            'price_per_night' => 'sometimes|numeric|min:0',
            'description'     => 'sometimes|nullable|string|max:500',
            'is_available'    => 'sometimes|boolean',
            'row'             => 'sometimes|nullable|integer|min:0',
            'column'          => 'sometimes|nullable|integer|min:0',
        ]);

        // Csak a validált mezőket frissítjük (mass assignment védelem)
        $spot->fill($validated);
        
        $spot->save();

        return response()->json([
            'message' => 'Kemping hely frissítve!',
            'spot' => $spot
        ], 200);
    }

    // Kemping hely törlése (csak tulajdonos)
    public function destroy(Request $request, $campingId, $spotId)
    {
        // Felhasználó ellenőrzése
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Nincs bejelentkezve'], 401);
        }

        // Kemping megkeresése
        $camping = Camping::find($campingId);

        if (!$camping) {
            return response()->json(['message' => 'Kemping nem található'], 404);
        }

        // csak a tulajdonos törölheti
        if ($camping->user_id != $user->id) {
            return response()->json(['message' => 'Nincs jogosultságod!'], 403);
        }

        // Hely megkeresése
        $spot = CampingSpot::where('camping_id', $campingId)
            ->where('spot_id', $spotId)
            ->first();

        if (!$spot) {
            return response()->json(['message' => 'Kemping hely nem található'], 404);
        }

        // Törlés
        $spot->delete();

        return response()->json(['message' => 'Kemping hely törölve!'], 200);
    }
}
