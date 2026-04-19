<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Camping;
use App\Models\CampingSpot;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

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

        // Számláló-alapú review tracking: kempingenként hány véleményt írt a user
        $completedCampingIds = $bookings->getCollection()
            ->where('status', 'completed')
            ->pluck('camping_id')
            ->unique()
            ->values();

        $reviewCounts = [];
        if ($completedCampingIds->isNotEmpty()) {
            $reviewCounts = Comment::where('user_id', $user_id)
                ->whereNull('parent_id')
                ->whereIn('camping_id', $completedCampingIds)
                ->groupBy('camping_id')
                ->selectRaw('camping_id, count(*) as cnt')
                ->pluck('cnt', 'camping_id')
                ->toArray();
        }

        // Kempingenként a legrégebbi N befejezett foglalást jelöljük "reviewed"-ként
        $campingIndex = [];
        foreach ($bookings->getCollection()->sortBy('arrival_date') as $booking) {
            if ($booking->status !== 'completed') continue;
            $cid = $booking->camping_id;
            $campingIndex[$cid] = ($campingIndex[$cid] ?? 0) + 1;
            $booking->has_review = $campingIndex[$cid] <= ($reviewCounts[$cid] ?? 0);
        }

        return response()->json($bookings);
    }

    public function getAllBookings(Request $request)
    {
        if (!$request->user()->is_superuser) {
            return response()->json(['message' => 'Nincs jogosultságod.'], 403);
        }

        $bookings = Booking::with(['user', 'camping.location', 'campingSpot'])
            ->orderBy('id', 'asc')
            ->paginate(20);

        return response()->json($bookings);
    }

    public function getPrices(Request $request)
    {
        if (!$request->user()->is_superuser) {
            return response()->json(['message' => 'Nincs jogosultságod.'], 403);
        }

        // postgresql: date kulonbseg EXTRACT-tal szamolva (napokban)
        $totalPrices = DB::table('bookings')
        ->join('camping_spots', 'camping_spots.spot_id', '=', 'bookings.camping_spot_id')
        ->selectRaw('
            bookings.id AS booking_id,
            EXTRACT(EPOCH FROM (bookings.departure_date::timestamp - bookings.arrival_date::timestamp)) / 86400
            * camping_spots.price_per_night AS total_price'
        )
        ->orderBy('bookings.id', 'asc')
        ->get();    
        return response()->json($totalPrices);
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
                                // Az új érkezés a meglévő foglalás közé esik.
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

        // Régebbi foglalásoknál előfordulhat, hogy hiányzik a QR kód.
        if (empty($booking->qr_code)) {
            $randomString = strtoupper(Str::random(8));
            $booking->qr_code = 'USR' . $booking->user_id . '-BKG' . $booking->id . '-' . $randomString;
            $booking->save();
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
        
        // Ha kértek státusz szűrést — csak érvényes státuszok engedélyezettek
        if ($request->has('status')) {
            $request->validate([
                'status' => 'in:pending,confirmed,checked_in,completed,cancelled'
            ]);
            $bookings = $bookings->where('status', $request->status);
        }
        
        // Ha kértek kemping szűrést
        if ($request->has('camping_id')) {
            $bookings = $bookings->where('camping_id', $request->camping_id);
        }
        
        $bookings = $bookings->orderBy('arrival_date', 'desc')
            ->get();

        return response()->json($bookings);
    }

    /**
     * POST /api/bookings/scan-image
     *
     * Az ESP32 által küldött JPEG képből QR kódot dekódol,
     * majd bejelentkezteti a vendéget.
     *
     * Auth: Authorization: Bearer <kemping_auth_token>
     *   – a token egyedi kempingenként, a tulajdonos generálja
     *     a POST /api/campings/{id}/esp32-token végponton.
     */
    public function scanImage(Request $request)
    {
        $tmpFile = null;

        try {
            // Kapu token ellenőrzés
            $authHeader    = $request->header('Authorization', '');
            $providedToken = str_starts_with($authHeader, 'Bearer ')
                ? trim(substr($authHeader, 7))
                : '';

            if ($providedToken === '') {
                return response()->json([
                    'valid'   => false,
                    'message' => 'Hiányzó hitelesítési token.'
                ], 401);
            }

            $gate = \App\Models\EntranceGate::whereRaw('auth_token = ?', [$providedToken])
                ->with('camping')
                ->first();

            if (!$gate) {
                return response()->json([
                    'valid'   => false,
                    'message' => 'Érvénytelen token – kapu nem található.'
                ], 401);
            }

            $camping = $gate->camping;

            // Postman/diagnosztikai fallback: ha qr_code mező érkezik, nem futtatunk Node dekódolást.
            $providedQrCode = trim((string) $request->input('qr_code', ''));
            if ($providedQrCode !== '') {
                $qrCode = $providedQrCode;
                \Log::info('[scan-image] Direkt qr_code használat (dekóder kihagyva)', [
                    'camping_id' => $camping->id,
                ]);
            } else {
                $tmpFile = $this->createScanTempImage($request);

                \Log::info('[scan-image] Temp fájl mentve', [
                    'path'       => $tmpFile,
                    'size_bytes' => @filesize($tmpFile) ?: 0,
                    'camping_id' => $camping->id,
                ]);

                $decode = $this->decodeQrFromTempImage($tmpFile);
                if (!($decode['success'] ?? false)) {
                    \Log::warning('[scan-image] QR dekódolás sikertelen', [
                        'camping_id' => $camping->id,
                        'kind'       => $decode['kind'] ?? 'unknown',
                        'error'      => $decode['error'] ?? 'Ismeretlen hiba',
                        'node_exit'  => $decode['node_exit'] ?? null,
                        'node_raw'   => $decode['node_raw'] ?? null,
                    ]);

                    if (($decode['kind'] ?? 'unknown') !== 'no_qr') {
                        return response()->json([
                            'valid'   => false,
                            'message' => 'A QR dekódoló szolgáltatás átmenetileg nem elérhető.',
                        ], 503);
                    }

                    return response()->json([
                        'valid'   => false,
                        'message' => 'Nem található QR kód a képen.',
                        'detail'  => $decode['error'] ?? 'Ismeretlen hiba a QR dekódolás során.',
                    ], 422);
                }

                $qrCode = trim((string) ($decode['data'] ?? ''));
                \Log::info('[scan-image] QR dekódolva', [
                    'qr_code'    => $qrCode,
                    'camping_id' => $camping->id,
                ]);
            }

            $qrCodeLower = mb_strtolower(trim($qrCode));
            $qrCodeLookup = $this->normalizeQrForLookup($qrCode);

            $booking = Booking::whereRaw('LOWER(qr_code) = ?', [$qrCodeLower])
                ->where('camping_id', $camping->id)
                ->with(['user', 'campingSpot'])
                ->first();

            if (!$booking) {
                $candidate = Booking::where('camping_id', $camping->id)
                    ->select(['id', 'qr_code'])
                    ->get()
                    ->first(function ($b) use ($qrCodeLookup) {
                        return $this->normalizeQrForLookup((string) $b->qr_code) === $qrCodeLookup;
                    });

                if ($candidate) {
                    $booking = Booking::where('id', $candidate->id)
                        ->with(['user', 'campingSpot'])
                        ->first();
                }
            }

            if (!$booking) {
                $bookingDifferentCamping = Booking::whereRaw('LOWER(qr_code) = ?', [$qrCodeLower])->first();
                if ($bookingDifferentCamping) {
                    return response()->json([
                        'valid'   => false,
                        'message' => 'A QR kód létezik, de másik kempinghez tartozik.',
                    ], 403);
                }

                return response()->json([
                    'valid'   => false,
                    'message' => 'Érvénytelen QR kód – nem található foglalás.',
                ], 404);
            }

            if ($booking->status === 'cancelled') {
                return response()->json([
                    'valid'   => false,
                    'message' => 'Ez a foglalás le lett mondva.',
                    'user_id' => (int) $booking->user_id,
                ], 422);
            }

            $arrivalDate   = optional($booking->arrival_date)->format('Y-m-d');
            $departureDate = optional($booking->departure_date)->format('Y-m-d');
            $today         = now()->toDateString();

            if (!$arrivalDate || !$departureDate) {
                \Log::error('[scan-image] Hiányzó vagy hibás dátum a foglalásban', [
                    'booking_id' => $booking->id,
                    'camping_id' => $camping->id,
                ]);

                return response()->json([
                    'valid'   => false,
                    'message' => 'A foglalás dátumai hibásak.',
                ], 422);
            }

            if ($today < $arrivalDate) {
                return response()->json([
                    'valid'   => false,
                    'message' => 'Ez a foglalás csak ' . $arrivalDate . '-től érvényes.',
                    'user_id' => (int) $booking->user_id,
                ], 422);
            }

            if ($today > $departureDate) {
                return response()->json([
                    'valid'   => false,
                    'message' => 'Ez a foglalás ' . $departureDate . '-ig volt érvényes.',
                    'user_id' => (int) $booking->user_id,
                ], 422);
            }

            if ($booking->status === 'pending' || $booking->status === 'confirmed') {
                $booking->status = 'checked_in';
                $booking->save();
            }

            return response()->json([
                'valid'   => true,
                'message' => 'Sikeres bejelentkezés!',
                'user_id' => (int) $booking->user_id,
            ]);
        } catch (\Throwable $e) {
            \Log::error('[scan-image] Váratlan backend hiba', [
                'error'       => $e->getMessage(),
                'file'        => $e->getFile(),
                'line'        => $e->getLine(),
                'content_type'=> $request->header('Content-Type', ''),
                'length'      => $request->header('Content-Length', ''),
            ]);

            return response()->json([
                'valid'   => false,
                'message' => 'A szkenner szolgáltatás átmenetileg nem elérhető.',
            ], 503);
        } finally {
            if ($tmpFile && is_file($tmpFile)) {
                @unlink($tmpFile);
            }
        }
    }

    private function createScanTempImage(Request $request): string
    {
        $contentType = strtolower($request->header('Content-Type', ''));
        $isRawJpeg   = str_starts_with($contentType, 'image/jpeg') || str_starts_with($contentType, 'image/jpg');
        $isRawPng    = str_starts_with($contentType, 'image/png');
        $maxBytes    = (int) env('ESP32_SCAN_MAX_BYTES', 3 * 1024 * 1024);

        if ($isRawJpeg || $isRawPng) {
            $imageData = $request->getContent();
            $size      = strlen((string) $imageData);

            if ($size === 0) {
                throw new \RuntimeException('Üres kép body.');
            }
            if ($size > $maxBytes) {
                throw new \RuntimeException('Túl nagy képméret.');
            }

            $ext = $isRawPng ? '.png' : '.jpg';
            $tmp = tempnam(sys_get_temp_dir(), 'esp32_qr_');
            if ($tmp === false) {
                throw new \RuntimeException('Nem sikerült ideiglenes fájlt létrehozni.');
            }
            $tmpFile = $tmp . $ext;
            @rename($tmp, $tmpFile);

            if (file_put_contents($tmpFile, $imageData) === false) {
                throw new \RuntimeException('Nem sikerült a képet ideiglenes fájlba menteni.');
            }

            return $tmpFile;
        }

        $request->validate([
            'image' => 'required|file|mimes:jpeg,jpg,png|max:10240',
        ]);

        $uploadedFile = $request->file('image');
        $raw          = file_get_contents($uploadedFile->getRealPath());
        if ($raw === false || $raw === '') {
            throw new \RuntimeException('A feltöltött kép nem olvasható.');
        }

        $ext = strtolower($uploadedFile->getClientOriginalExtension()) === 'png' ? '.png' : '.jpg';
        $tmp = tempnam(sys_get_temp_dir(), 'esp32_qr_');
        if ($tmp === false) {
            throw new \RuntimeException('Nem sikerült ideiglenes fájlt létrehozni.');
        }
        $tmpFile = $tmp . $ext;
        @rename($tmp, $tmpFile);

        if (file_put_contents($tmpFile, $raw) === false) {
            throw new \RuntimeException('Nem sikerült a feltöltött képet ideiglenes fájlba menteni.');
        }

        return $tmpFile;
    }

    private function decodeQrFromTempImage(string $tmpFile): array
    {
        $scriptDir  = base_path('scripts');
        $scriptPath = $scriptDir . DIRECTORY_SEPARATOR . 'decode-qr.js';
        if (!is_file($scriptPath)) {
            return [
                'success'  => false,
                'kind'     => 'runtime',
                'error'    => 'QR dekódoló script nem található.',
                'node_exit'=> null,
                'node_raw' => null,
            ];
        }

        $nodeCandidates = $this->getNodeCandidates();

        $timeout             = (float) env('QR_DECODE_TIMEOUT_SECONDS', 8);
        $lastError           = null;
        $hadHealthyCandidate = false;

        foreach ($nodeCandidates as $nodeBin) {
            if (!$this->isHealthyNodeBinary($nodeBin)) {
                if (!$hadHealthyCandidate && $lastError === null) {
                    $probeInfo = $this->probeNodeBinary($nodeBin);
                    $lastError = [
                        'success'   => false,
                        'kind'      => 'runtime',
                        'error'     => 'A Node futtatókörnyezet hibás vagy nem használható: ' . $nodeBin,
                        'node_exit' => $probeInfo['exit'],
                        'node_raw'  => trim(($probeInfo['out'] ?? '') . "\n" . ($probeInfo['err'] ?? '')),
                    ];
                }
                continue;
            }

            $hadHealthyCandidate = true;

            $process = new Process([$nodeBin, 'decode-qr.js', $tmpFile], $scriptDir, $this->buildNodeProcessEnv());
            $process->setTimeout($timeout);
            $process->setIdleTimeout($timeout);

            try {
                $process->run();
            } catch (ProcessTimedOutException $e) {
                if ($lastError === null) {
                    $lastError = [
                        'success'   => false,
                        'kind'      => 'timeout',
                        'error'     => 'QR dekódolás időtúllépés.',
                        'node_exit' => $process->getExitCode(),
                        'node_raw'  => trim($process->getOutput() . "\n" . $process->getErrorOutput()),
                    ];
                }
                continue;
            }

            $stdout    = trim($process->getOutput());
            $stderr    = trim($process->getErrorOutput());
            $rawOutput = trim($stdout . ($stderr !== '' ? "\n" . $stderr : ''));
            $decoded   = json_decode($stdout, true);

            if ($decoded && ($decoded['success'] ?? false)) {
                return [
                    'success'   => true,
                    'kind'      => 'ok',
                    'data'      => $decoded['data'] ?? '',
                    'node_exit' => $process->getExitCode(),
                    'node_raw'  => $rawOutput,
                ];
            }

            $errorMessage = is_array($decoded)
                ? ($decoded['error'] ?? ($rawOutput !== '' ? $rawOutput : 'Ismeretlen hiba a QR dekódolás során.'))
                : ($rawOutput !== '' ? $rawOutput : 'Ismeretlen hiba a QR dekódolás során.');

            $isNoQr = is_array($decoded)
                && ($decoded['success'] ?? null) === false
                && (($decoded['error'] ?? '') === 'Nem található QR kód a képen.');

            if ($isNoQr) {
                return [
                    'success'   => false,
                    'kind'      => 'no_qr',
                    'error'     => $errorMessage,
                    'node_exit' => $process->getExitCode(),
                    'node_raw'  => $rawOutput,
                ];
            }

            $isRuntimeCrash = str_contains($errorMessage, 'Assertion failed')
                || str_contains($errorMessage, 'node::InitializeOncePerProcessInternal')
                || str_contains($errorMessage, 'Native stack trace')
                || str_contains($errorMessage, 'CSPRNG')
                || str_contains($errorMessage, 'not recognized as an internal or external command')
                || str_contains($errorMessage, 'No such file or directory');

            $lastError = [
                'success'   => false,
                'kind'      => $isRuntimeCrash ? 'runtime' : 'decode_error',
                'error'     => $errorMessage,
                'node_exit' => $process->getExitCode(),
                'node_raw'  => $rawOutput,
            ];
        }

        return $lastError ?? [
            'success'   => false,
            'kind'      => 'runtime',
            'error'     => 'Nem található használható Node futtatókörnyezet.',
            'node_exit' => null,
            'node_raw'  => null,
        ];
    }

    private function getNodeCandidates(): array
    {
        $candidates = [
            trim((string) env('NODE_PATH', '')),
            'node',
        ];

        if (DIRECTORY_SEPARATOR === '\\') {
            // Gyakori Windows telepítési útvonalak explicit fallbackként
            $localAppData = getenv('LOCALAPPDATA') ?: '';
            $programFiles = getenv('ProgramFiles') ?: 'C:\\Program Files';
            $programFilesX86 = getenv('ProgramFiles(x86)') ?: 'C:\\Program Files (x86)';

            $candidates[] = $programFiles . '\\nodejs\\node.exe';
            $candidates[] = $programFilesX86 . '\\nodejs\\node.exe';
            if ($localAppData !== '') {
                $candidates[] = $localAppData . '\\Programs\\nodejs\\node.exe';
            }
        }

        if (DIRECTORY_SEPARATOR === '\\') {
            $where = new Process(['where', 'node'], null, $this->buildNodeProcessEnv());
            $where->setTimeout(2);
            $where->run();

            if ($where->isSuccessful()) {
                $lines = preg_split('/\r\n|\n|\r/', trim($where->getOutput())) ?: [];
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line !== '') {
                        $candidates[] = $line;
                    }
                }
            }
        } else {
            $which = new Process(['which', 'node'], null, $this->buildNodeProcessEnv());
            $which->setTimeout(2);
            $which->run();

            if ($which->isSuccessful()) {
                $path = trim($which->getOutput());
                if ($path !== '') {
                    $candidates[] = $path;
                }
            }
        }

        $resolved = [];
        foreach ($candidates as $candidate) {
            $normalized = $this->normalizeNodeCandidate((string) $candidate);
            if ($normalized !== null) {
                $resolved[] = $normalized;
            }
        }

        return array_values(array_unique($resolved));
    }

    private function normalizeNodeCandidate(string $candidate): ?string
    {
        $candidate = trim($candidate, " \t\n\r\0\x0B\"");
        if ($candidate === '') {
            return null;
        }

        // Közvetlen, abszolút útvonalnál csak valódi node binárist fogadunk el.
        if ($this->isAbsolutePath($candidate)) {
            if (is_file($candidate) && $this->isLikelyNodeExecutable($candidate)) {
                return $candidate;
            }
            return null;
        }

        // Relatív/alias név esetén feloldjuk abszolút útvonalra.
        if (DIRECTORY_SEPARATOR === '\\') {
            $where = new Process(['where', $candidate], null, $this->buildNodeProcessEnv());
            $where->setTimeout(2);
            $where->run();
            if (!$where->isSuccessful()) {
                return null;
            }

            $lines = preg_split('/\r\n|\n|\r/', trim($where->getOutput())) ?: [];
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line !== '' && is_file($line) && $this->isLikelyNodeExecutable($line)) {
                    return $line;
                }
            }
            return null;
        }

        $which = new Process(['which', $candidate], null, $this->buildNodeProcessEnv());
        $which->setTimeout(2);
        $which->run();
        if (!$which->isSuccessful()) {
            return null;
        }

        $path = trim($which->getOutput());
        if ($path !== '' && is_file($path) && $this->isLikelyNodeExecutable($path)) {
            return $path;
        }

        return null;
    }

    private function isAbsolutePath(string $path): bool
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return (bool) preg_match('/^[A-Za-z]:\\\\/', $path) || str_starts_with($path, '\\\\');
        }

        return str_starts_with($path, '/');
    }

    private function isLikelyNodeExecutable(string $path): bool
    {
        $basename = strtolower(pathinfo($path, PATHINFO_BASENAME));
        return in_array($basename, ['node', 'node.exe', 'node.cmd'], true);
    }

    private function isHealthyNodeBinary(string $nodeBin): bool
    {
        $probeInfo = $this->probeNodeBinary($nodeBin);
        return $probeInfo['ok'] === true;
    }

    private function probeNodeBinary(string $nodeBin): array
    {
        if (!$this->isLikelyNodeExecutable($nodeBin)) {
            return [
                'ok'   => false,
                'exit' => null,
                'out'  => '',
                'err'  => 'Érvénytelen Node futtatható fájl jelölt: ' . $nodeBin,
            ];
        }

        $probe = new Process([
            $nodeBin,
            '-e',
            'const c=require("crypto"); c.randomBytes(8); process.stdout.write("ok")',
        ], null, $this->buildNodeProcessEnv());
        $probe->setTimeout(5);

        try {
            $probe->run();
        } catch (\Throwable $e) {
            return [
                'ok'   => false,
                'exit' => null,
                'out'  => '',
                'err'  => $e->getMessage(),
            ];
        }

        return [
            'ok'   => $probe->isSuccessful() && trim($probe->getOutput()) === 'ok',
            'exit' => $probe->getExitCode(),
            'out'  => trim($probe->getOutput()),
            'err'  => trim($probe->getErrorOutput()),
        ];
    }

    private function buildNodeProcessEnv(): array
    {
        $env = [];

        if (DIRECTORY_SEPARATOR === '\\') {
            // Windows alatt ezek hiánya okozhat CSPRNG/OpenSSL init hibát child processben.
            $env['SystemRoot'] = getenv('SystemRoot') ?: 'C:\\Windows';
            $env['WINDIR'] = getenv('WINDIR') ?: 'C:\\Windows';
            $env['ComSpec'] = getenv('ComSpec') ?: 'C:\\Windows\\System32\\cmd.exe';
            $env['TEMP'] = getenv('TEMP') ?: sys_get_temp_dir();
            $env['TMP'] = getenv('TMP') ?: sys_get_temp_dir();

            $path = getenv('PATH') ?: '';
            $systemPaths = [
                'C:\\Windows\\System32',
                'C:\\Windows',
            ];

            foreach ($systemPaths as $p) {
                if ($path === '') {
                    $path = $p;
                    continue;
                }

                if (stripos($path, $p) === false) {
                    $path .= ';' . $p;
                }
            }

            $env['PATH'] = $path;
        }

        return $env;
    }

    private function normalizeQrForLookup(string $value): string
    {
        $normalized = mb_strtoupper(trim($value));
        $normalized = preg_replace('/\s+/u', '', $normalized) ?? $normalized;
        return $normalized;
    }
}
