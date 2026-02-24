<?php

namespace App\Http\Controllers;

use App\Models\EntranceGate;
use App\Models\Camping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EntranceGateController extends Controller
{
    /**
     * GET /api/campings/{campingId}/gates
     * A kemping összes kapujának listázása (csak tulajdonosnak).
     */
    public function index(Request $request, $campingId)
    {
        $camping = Camping::find($campingId);

        if (!$camping) {
            return response()->json(['message' => 'Kemping nem található.'], 404);
        }

        if ($camping->user_id != $request->user()->id) {
            return response()->json(['message' => 'Nincs jogosultságod!'], 403);
        }

        $gates = EntranceGate::where('camping_id', $campingId)
            ->select(['id', 'gate_id', 'name', 'opening_time', 'closing_time', 'created_at',
                DB::raw("CASE WHEN auth_token IS NOT NULL THEN CONCAT(LEFT(auth_token, 8), '...') ELSE NULL END AS token_prefix"),
                DB::raw("CASE WHEN auth_token IS NOT NULL THEN true ELSE false END AS has_token"),
            ])
            ->get();

        return response()->json($gates);
    }

    /**
     * POST /api/campings/{campingId}/gates
     * Új kapu létrehozása (csak tulajdonosnak).
     */
    public function store(Request $request, $campingId)
    {
        $camping = Camping::find($campingId);

        if (!$camping) {
            return response()->json(['message' => 'Kemping nem található.'], 404);
        }

        if ($camping->user_id != $request->user()->id) {
            return response()->json(['message' => 'Nincs jogosultságod!'], 403);
        }

        $validated = $request->validate([
            'gate_id'      => 'nullable|integer',
            'name'         => 'nullable|string|max:100',
            'opening_time' => 'nullable|date_format:H:i',
            'closing_time' => 'nullable|date_format:H:i',
        ]);

        $gate = EntranceGate::create([
            'camping_id'   => $campingId,
            'name'         => $validated['name'] ?? null,
            'gate_id'      => $validated['gate_id'] ?? null,
            'opening_time' => $validated['opening_time'] ?? null,
            'closing_time' => $validated['closing_time'] ?? null,
            'timestamp'    => now(),
        ]);

        return response()->json([
            'message' => 'Kapu sikeresen létrehozva.',
            'gate'    => $gate,
        ], 201);
    }

    /**
     * PUT /api/campings/{campingId}/gates/{gateId}
     * Kapu adatainak módosítása (csak tulajdonosnak).
     */
    public function update(Request $request, $campingId, $gateId)
    {
        $gate = $this->findGateForOwner($campingId, $gateId, $request->user()->id);
        if ($gate instanceof \Illuminate\Http\JsonResponse) return $gate;

        $validated = $request->validate([
            'gate_id'      => 'sometimes|nullable|integer',
            'name'         => 'nullable|string|max:100',
            'opening_time' => 'sometimes|nullable|date_format:H:i',
            'closing_time' => 'sometimes|nullable|date_format:H:i',
        ]);

        $gate->fill($validated)->save();

        return response()->json(['message' => 'Kapu frissítve.', 'gate' => $gate]);
    }

    /**
     * DELETE /api/campings/{campingId}/gates/{gateId}
     * Kapu törlése (csak tulajdonosnak).
     */
    public function destroy(Request $request, $campingId, $gateId)
    {
        $gate = $this->findGateForOwner($campingId, $gateId, $request->user()->id);
        if ($gate instanceof \Illuminate\Http\JsonResponse) return $gate;

        $gate->delete();

        return response()->json(['message' => 'Kapu törölve.']);
    }

    /**
     * POST /api/campings/{campingId}/gates/{gateId}/esp32-token
     * Új token generálása ehhez a kapuhoz.
     * A teljes token csak egyszer jelenik meg – utána már csak prefix látható.
     */
    public function generateToken(Request $request, $campingId, $gateId)
    {
        $gate = $this->findGateForOwner($campingId, $gateId, $request->user()->id);
        if ($gate instanceof \Illuminate\Http\JsonResponse) return $gate;

        $token = Str::random(16);
        $gate->auth_token = $token;
        $gate->save();

        return response()->json([
            'message'      => 'Token generálva. Mentsd el, többé nem jelenik meg teljes egészében!',
            'auth_token'   => $token,
            'gate_id'      => $gate->id,
            'camping_id'   => $gate->camping_id,
        ]);
    }

    /**
     * GET /api/campings/{campingId}/gates/{gateId}/esp32-token
     * Token státusz lekérése (létezik-e, első 8 karakter).
     */
    public function getTokenStatus(Request $request, $campingId, $gateId)
    {
        $gate = $this->findGateForOwner($campingId, $gateId, $request->user()->id);
        if ($gate instanceof \Illuminate\Http\JsonResponse) return $gate;

        $hasToken = !empty($gate->getRawOriginal('auth_token'));

        return response()->json([
            'has_token'    => $hasToken,
            'token_prefix' => $hasToken ? substr($gate->getRawOriginal('auth_token'), 0, 8) . '...' : null,
            'gate_id'      => $gate->id,
            'camping_id'   => $gate->camping_id,
        ]);
    }

    /**
     * DELETE /api/campings/{campingId}/gates/{gateId}/esp32-token
     * Token visszavonása (az ESP32 ezután nem tud bejelentkeztetni).
     */
    public function revokeToken(Request $request, $campingId, $gateId)
    {
        $gate = $this->findGateForOwner($campingId, $gateId, $request->user()->id);
        if ($gate instanceof \Illuminate\Http\JsonResponse) return $gate;

        $gate->auth_token = null;
        $gate->save();

        return response()->json(['message' => 'Token visszavonva. A szkenner többé nem tud bejelentkeztetni.']);
    }

    //  error helper


    private function findGateForOwner($campingId, $gateId, $userId)
    {
        $camping = Camping::find($campingId);

        if (!$camping) {
            return response()->json(['message' => 'Kemping nem található.'], 404);
        }

        if ($camping->user_id != $userId) {
            return response()->json(['message' => 'Nincs jogosultságod!'], 403);
        }

        $gate = EntranceGate::where('id', $gateId)
            ->where('camping_id', $campingId)
            ->first();

        if (!$gate) {
            return response()->json(['message' => 'Kapu nem található.'], 404);
        }

        return $gate;
    }
}
