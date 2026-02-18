<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Camping;
use App\Models\CampingSpot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    public function index(Request $request)
    {

        $user_id = $request->user()->id;
        
        // Lekérdezzük az összes foglalást        
        $bookings = Booking::where('user_id', $user_id)
            ->with(['camping.location', 'camping.photos', 'campingSpot'])
            ->orderBy('arrival_date', 'desc')
            ->paginate(10);

        return response()->json($bookings);
    }

    public function getAllBookings(Request $request)
    {
        $bookings = Booking::with(['user', 'camping.location', 'campingSpot'])
            ->orderBy('id', 'asc')
            ->paginate(20);

        return response()->json($bookings);
    }

    public function store(Request $request)
    {
        // Minden szügséges adat ellenőrzése
        $validated = $request->validate([
            'camping_id' => 'required|exists:campings,id',
            'camping_spot_id' => 'required|exists:camping_spots,spot_id',
            'arrival_date' => 'required|date|after_or_equal:today',
            'departure_date' => 'required|date|after:arrival_date',
            'guests' => 'required|integer|min:1'
        ]);

        // Kiválasztott hely megkeresése
        $spot = CampingSpot::find($validated['camping_spot_id']);
        
        if (!$spot) {
            return response()->json([
                'message' => 'Ez a hely nem található.'
            ], 404);
        }
        
        // Ellenőrizzük hogy a hely valóban ehhez a kempinghez tartozik-e
        if ($spot->camping_id != $validated['camping_id']) {
            return response()->json([
                'message' => 'Ez a hely nem ehhez a kempinghez tartozik.'
            ], 422);
        }
        
        // Megnézzük hogy a hely elérhető-e
        if ($spot->is_available == false) {
            return response()->json([
                'message' => 'Ez a hely jelenleg nem elérhető.'
            ], 422);
        }

        // Megnézzük hogy elfér-e ennyi ember
        if ($validated['guests'] > $spot->capacity) {
            return response()->json([
                'message' => "Ez a hely maximum " . $spot->capacity . " fő részére alkalmas."
            ], 422);
        }

        // Megnézzük hogy már foglalt-e a hely ebben az időszakban
        $overlapping_bookings = Booking::where('camping_spot_id', $validated['camping_spot_id'])
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($validated) {
                // az új érkezés a meglévő foglalás közé esik        ????????
                $query->where(function ($q) use ($validated) {
                    $q->where('arrival_date', '<=', $validated['arrival_date'])
                      ->where('departure_date', '>=', $validated['arrival_date']);
                })
                // az új távozás a meglévő foglalás közé esik
                ->orWhere(function ($q) use ($validated) {
                    $q->where('arrival_date', '<=', $validated['departure_date'])
                      ->where('departure_date', '>=', $validated['departure_date']);
                })
                // az új foglalás teljesen magában foglalja a meglévőt
                ->orWhere(function ($q) use ($validated) {
                    $q->where('arrival_date', '>=', $validated['arrival_date'])
                      ->where('departure_date', '<=', $validated['departure_date']);
                });
            })
            ->count();

        if ($overlapping_bookings > 0) {
            return response()->json([
                'message' => 'Ez a hely már foglalt a megadott időszakban.'
            ], 422);
        }

        // Foglalás létrehozzása
        $booking = new Booking();
        $booking->user_id = $request->user()->id;
        $booking->camping_id = $validated['camping_id'];
        $booking->camping_spot_id = $validated['camping_spot_id'];
        $booking->arrival_date = $validated['arrival_date'];
        $booking->departure_date = $validated['departure_date'];
        $booking->status = 'pending';
        $booking->save();

        // QR kódhoz string generálása
        $user_id = $request->user()->id;
        $booking_id = $booking->id;
        $random_string = strtoupper(Str::random(8));
        $qr_code = 'USR' . $user_id . '-BKG' . $booking_id . '-' . $random_string;
        
        // Hozzáadjuk a string-et a booking táblához
        $booking->qr_code = $qr_code;
        $booking->save();

        // Betöltjük a kapcsolódó adatokat -> a részletes api válasz miatt kell
        $booking->load(['camping.location', 'camping.photos', 'campingSpot']);

        return response()->json([
            'message' => 'Foglalás sikeresen létrehozva!',
            'booking' => $booking
        ], 201);
    }

    public function show(Request $request, $id)
    {
        // Megkeressük a foglalást
        $booking = Booking::with(['camping.location', 'camping.photos', 'campingSpot', 'user'])
            ->find($id);
            
        if (!$booking) {
            return response()->json([
                'message' => 'A foglalás nem található.'
            ], 404);
        }

        $user_id = $request->user()->id;
        
        // Megnézzük, hogy a user/owner megegyezik-e a camping vagy a booking user id-val
        $is_own_booking = ($booking->user_id == $user_id);
        $is_camping_owner = Camping::where('id', $booking->camping_id)
            ->where('user_id', $user_id)
            ->exists();
            
        if (!$is_own_booking && !$is_camping_owner) {
            return response()->json([
                'message' => 'Nincs jogosultságod ehhez a foglaláshoz.'
            ], 403);
        }

        // Hány éjszakás a foglalás
        $arrival = new \DateTime($booking->arrival_date);
        $departure = new \DateTime($booking->departure_date);
        $nights = $arrival->diff($departure)->days;
        
        // Teljes ár kiszámolása
        $booking->nights = $nights;
        $booking->total_price = $nights * $booking->campingSpot->price_per_night;

        return response()->json($booking);
    }

    public function update(Request $request, $id)
    {
        $booking = Booking::find($id);
        
        if (!$booking) {
            return response()->json([
                'message' => 'A foglalás nem található.'
            ], 404);
        }

        // Csak saját foglalást lehet módosítani
        if ($booking->user_id != $request->user()->id) {
            return response()->json([
                'message' => 'Nincs jogosultságod ehhez a foglaláshoz.'
            ], 403);
        }

        // Csak pending (függőben lévő) foglalást lehet módosítani
        if ($booking->status != 'pending') {
            return response()->json([
                'message' => 'Csak függőben lévő foglalást lehet módosítani.'
            ], 422);
        }

        // Új dátumok ellenőrzése
        $validated = $request->validate([
            'arrival_date' => 'required|date|after_or_equal:today',
            'departure_date' => 'required|date|after:arrival_date',
        ]);

        // Megnézzük, hogy az új dátumok nem ütköznek más foglalással
        $overlapping_bookings = Booking::where('camping_spot_id', $booking->camping_spot_id)
            ->where('id', '!=', $booking->id)
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($validated) {
                $query->where(function ($q) use ($validated) {
                    $q->where('arrival_date', '<=', $validated['arrival_date'])
                      ->where('departure_date', '>=', $validated['arrival_date']);
                })
                ->orWhere(function ($q) use ($validated) {
                    $q->where('arrival_date', '<=', $validated['departure_date'])
                      ->where('departure_date', '>=', $validated['departure_date']);
                })
                ->orWhere(function ($q) use ($validated) {
                    $q->where('arrival_date', '>=', $validated['arrival_date'])
                      ->where('departure_date', '<=', $validated['departure_date']);
                });
            })
            ->count();

        if ($overlapping_bookings > 0) {
            return response()->json([
                'message' => 'Ez a hely már foglalt a megadott időszakban.'
            ], 422);
        }

        // Frissítjük a dátumokat
        $booking->arrival_date = $validated['arrival_date'];
        $booking->departure_date = $validated['departure_date'];
        $booking->save();
        
        $booking->load(['camping.location', 'camping.photos', 'campingSpot']);

        return response()->json([
            'message' => 'Foglalás sikeresen módosítva!',
            'booking' => $booking
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $booking = Booking::find($id);
        
        if (!$booking) {
            return response()->json([
                'message' => 'A foglalás nem található.'
            ], 404);
        }

        $user_id = $request->user()->id;
        
        // Csak  saját foglalást vagy az adott kemping tulajdonosa törölheti
        $is_own_booking = ($booking->user_id == $user_id);
        $is_camping_owner = Camping::where('id', $booking->camping_id)
            ->where('user_id', $user_id)
            ->exists();
            
        if (!$is_own_booking && !$is_camping_owner) {
            return response()->json([
                'message' => 'Nincs jogosultságod ehhez a foglaláshoz.'
            ], 403);
        }

        // Már befejezett vagy lemondott foglalást ne lehessen törölni
        if ($booking->status == 'completed' || $booking->status == 'cancelled') {
            return response()->json([
                'message' => 'Ez a foglalás már nem törölhető.'
            ], 422);
        }

        // Státusz átállítása lemondottra (nem töröljük végleg)
        $booking->status = 'cancelled';
        $booking->save();

        return response()->json([
            'message' => 'Foglalás sikeresen lemondva.'
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $booking = Booking::find($id);
        
        if (!$booking) {
            return response()->json([
                'message' => 'A foglalás nem található.'
            ], 404);
        }

        // Csak a kemping tulajdonosa változtathatja a státuszt
        $is_camping_owner = Camping::where('id', $booking->camping_id)
            ->where('user_id', $request->user()->id)
            ->exists();
            
        if (!$is_camping_owner) {
            return response()->json([
                'message' => 'Nincs jogosultságod ehhez a művelethez.'
            ], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,checked_in,completed,cancelled'
        ]);

        // Frissítjük a státuszt
        $booking->status = $validated['status'];
        $booking->save();
        
        $booking->load(['camping.location', 'user', 'campingSpot']);

        return response()->json([
            'message' => 'Foglalás státusza sikeresen módosítva!',
            'booking' => $booking
        ]);
    }

    public function getQrCode(Request $request, $id)
    {
        $booking = Booking::find($id);
        
        if (!$booking) {
            return response()->json([
                'message' => 'A foglalás nem található.'
            ], 404);
        }

        // Csak a saját foglaláshoz kérhet 
        if ($booking->user_id != $request->user()->id) {
            return response()->json([
                'message' => 'Nincs jogosultságod ehhez a foglaláshoz.'
            ], 403);
        }

        return response()->json([
            'qr_code' => $booking->qr_code,
            'booking_id' => $booking->id,
            'camping_name' => $booking->camping->camping_name,
            'arrival_date' => $booking->arrival_date,
            'departure_date' => $booking->departure_date
        ]);
    }

    public function ownerBookings(Request $request)
    {
        $user_id = $request->user()->id;
        
        // Lekérdezzük a user saját kempingjeit
        $my_camping_ids = Camping::where('user_id', $user_id)
            ->pluck('id')
            ->toArray();

        // Lekérdezzük az ezekhez tartozó foglalásokat
        $bookings = Booking::whereIn('camping_id', $my_camping_ids)
            ->with(['user', 'camping.location', 'campingSpot']);
        
        // Ha kértek státusz szűrést
        if ($request->has('status')) {
            $bookings = $bookings->where('status', $request->status);
        }
        
        // Ha kértek kemping szűrést
        if ($request->has('camping_id')) {
            $bookings = $bookings->where('camping_id', $request->camping_id);
        }
        
        $bookings = $bookings->orderBy('arrival_date', 'desc')
            ->paginate(20);

        return response()->json($bookings);
    }

    public function scanQrCode(Request $request)
    {
        $validated = $request->validate([
            'qr_code' => 'required|string',
            'camping_id' => 'required|exists:campings,id'
        ]);

        // Megkeressük a foglalást 
        $booking = Booking::where('qr_code', $validated['qr_code'])
            ->where('camping_id', $validated['camping_id'])
            ->with(['user', 'campingSpot'])
            ->first();

        // Ha nincs foglalás 
        if (!$booking) {
            return response()->json([
                'valid' => false,
                'message' => 'Érvénytelen QR kód.'
            ], 404);
        }

        // Ha a foglalás le van mondva
        if ($booking->status == 'cancelled') {
            return response()->json([
                'valid' => false,
                'message' => 'Ez a foglalás le lett mondva.',
                'booking' => $booking
            ], 422);
        }

        // Megnézzük, hogy a mai nap benne van-e a foglalási időszakban
        $today = date('Y-m-d');
        
        if ($today <= $booking->arrival_date) {
            return response()->json([
                'valid' => false,
                'message' => 'Ez a foglalás csak ' . $booking->arrival_date . '-től érvényes.',
                'booking' => $booking
            ], 422);
        }
        
        if ($today >= $booking->departure_date) {
            return response()->json([
                'valid' => false,
                'message' => 'Ez a foglalás ' . $booking->departure_date . '-ig volt érvényes.',
                'booking' => $booking
            ], 422);
        }

        // Ha pending vagy confirmed akkor bejelentkeztetjük ????
        if ($booking->status == 'pending' || $booking->status == 'confirmed') {
            $booking->status = 'checked_in';
            $booking->save();
        }

        return response()->json([
            'valid' => true,
            'message' => 'Sikeres bejelentkezés!',
            'booking' => $booking
        ]);
    }
}
