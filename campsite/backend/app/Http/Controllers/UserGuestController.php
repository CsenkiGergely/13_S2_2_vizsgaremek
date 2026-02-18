<?php

namespace App\Http\Controllers;

use App\Models\UserGuest;
use Illuminate\Http\Request;

class UserGuestController extends Controller
{
    /**
     * A bejelentkezett felhasználó vendég adatainak listázása
     */
    public function index(Request $request)
    {
        $guests = UserGuest::where('user_id', $request->user()->id)->get();

        return response()->json($guests);
    }

    /**
     * Új vendég adat létrehozása
     */
    public function store(Request $request)
    {
        $fields = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'birth_date' => 'required|date',
            'place_of_birth' => 'nullable|string|max:255',
            'gender' => 'nullable|string|max:50',
            'citizenship' => 'nullable|string|max:100',
            'mothers_birth_name' => 'nullable|string|max:255',
            'id_card_number' => 'nullable|string|max:50',
            'passport_number' => 'nullable|string|max:50',
            'visa_flight_number' => 'nullable|string|max:50',
            'resident_permit_number' => 'nullable|string|max:50',
            'date_of_entry' => 'nullable|date',
            'place_of_entry' => 'nullable|string|max:255',
        ]);

        $fields['user_id'] = $request->user()->id;
        $guest = UserGuest::create($fields);

        return response()->json($guest, 201);
    }

    /**
     * Egy vendég adat megjelenítése
     */
    public function show(Request $request, string $id)
    {
        $guest = UserGuest::where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json($guest);
    }

    /**
     * Vendég adat frissítése
     */
    public function update(Request $request, string $id)
    {
        $guest = UserGuest::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $fields = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'birth_date' => 'sometimes|date',
            'place_of_birth' => 'nullable|string|max:255',
            'gender' => 'nullable|string|max:50',
            'citizenship' => 'nullable|string|max:100',
            'mothers_birth_name' => 'nullable|string|max:255',
            'id_card_number' => 'nullable|string|max:50',
            'passport_number' => 'nullable|string|max:50',
            'visa_flight_number' => 'nullable|string|max:50',
            'resident_permit_number' => 'nullable|string|max:50',
            'date_of_entry' => 'nullable|date',
            'place_of_entry' => 'nullable|string|max:255',
        ]);

        $guest->update($fields);

        return response()->json($guest);
    }

    /**
     * Vendég adat törlése
     */
    public function destroy(Request $request, string $id)
    {
        $guest = UserGuest::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $guest->delete();

        return response()->json(['message' => 'Vendég adat törölve.']);
    }
}
