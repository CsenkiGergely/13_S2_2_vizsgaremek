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
    /**
     * @OA\Get(
     *     path="/bookings",
     *     tags={"Bookings"},
     *     summary="Get user bookings",
     *     description="Get all bookings for authenticated user",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/bookings",
     *     tags={"Bookings"},
     *     summary="Create new booking",
     *     description="Create a new camping reservation",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"camping_id","camping_spot_id","arrival_date","departure_date","guests"},
     *             @OA\Property(property="camping_id", type="integer", example=1),
     *             @OA\Property(property="camping_spot_id", type="integer", example=1),
     *             @OA\Property(property="arrival_date", type="string", format="date", example="2026-07-10"),
     *             @OA\Property(property="departure_date", type="string", format="date", example="2026-07-15"),
     *             @OA\Property(property="guests", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Booking created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="booking", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/bookings/{id}",
     *     tags={"Bookings"},
     *     summary="Get booking details",
     *     description="Get detailed information about a specific booking",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Booking details",
     *         @OA\JsonContent(
     *             @OA\Property(property="booking", type="object"),
     *             @OA\Property(property="nights", type="integer"),
     *             @OA\Property(property="total_price", type="number")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Booking not found"),
     *     @OA\Response(response=403, description="Unauthorized access")
     * )
     */
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

    /**
     * @OA\Put(
     *     path="/bookings/{id}",
     *     tags={"Bookings"},
     *     summary="Update booking",
     *     description="Update an existing booking (only pending bookings)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="arrival_date", type="string", format="date"),
     *             @OA\Property(property="departure_date", type="string", format="date"),
     *             @OA\Property(property="guests", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Booking updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="booking", type="object")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=404, description="Booking not found"),
     *     @OA\Response(response=422, description="Validation error or booking not modifiable")
     * )
     */
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

    /**
     * @OA\Delete(
     *     path="/bookings/{id}",
     *     tags={"Bookings"},
     *     summary="Cancel booking",
     *     description="Cancel an existing booking (changes status to cancelled)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Booking cancelled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=404, description="Booking not found"),
     *     @OA\Response(response=422, description="Booking already cancelled")
     * )
     */
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

    /**
     * @OA\Patch(
     *     path="/bookings/{id}/status",
     *     tags={"Bookings"},
     *     summary="Update booking status",
     *     description="Update booking status (camping owner only)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"pending","confirmed","checked_in","completed","cancelled"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="booking", type="object")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized - not camping owner"),
     *     @OA\Response(response=404, description="Booking not found")
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/bookings/{id}/qr-code",
     *     tags={"Bookings"},
     *     summary="Get booking QR code",
     *     description="Get QR code string for a booking",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="QR code data",
     *         @OA\JsonContent(
     *             @OA\Property(property="qr_code", type="string"),
     *             @OA\Property(property="booking_id", type="integer"),
     *             @OA\Property(property="camping_name", type="string"),
     *             @OA\Property(property="arrival_date", type="string"),
     *             @OA\Property(property="departure_date", type="string")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=404, description="Booking not found")
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/owner/bookings",
     *     tags={"Bookings"},
     *     summary="Get owner bookings",
     *     description="Get all bookings for campings owned by the user",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of bookings for owned campings",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/scan-qr",
     *     tags={"Bookings"},
     *     summary="Scan QR code",
     *     description="Verify and check-in booking using QR code",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"qr_code","camping_id"},
     *             @OA\Property(property="qr_code", type="string", example="USR1-BKG5-ABC12345"),
     *             @OA\Property(property="camping_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="QR code validated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="booking", type="object"),
     *             @OA\Property(property="status", type="string")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Booking not found"),
     *     @OA\Response(response=422, description="Invalid booking status")
     * )
     */
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
        
        if ($today < $booking->arrival_date) {
            return response()->json([
                'valid' => false,
                'message' => 'Ez a foglalás csak ' . $booking->arrival_date . '-től érvényes.',
                'booking' => $booking
            ], 422);
        }
        
        if ($today > $booking->departure_date) {
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
