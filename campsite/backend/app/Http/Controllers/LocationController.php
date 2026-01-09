<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LocationController extends Controller
{
    /**
     * Autocomplete keresés városokra/helyekre Google Places API-val
     */
    public function autocomplete(Request $request)
    {
        $request->validate([
            'input' => 'required|string|min:2'
        ]);

        $apiKey = env('GOOGLE_PLACES_API_KEY');
        
        if (!$apiKey) {
            return response()->json([
                'error' => 'Google Places API key nincs beállítva'
            ], 500);
        }

        // Google Places Autocomplete API hívás
        $response = Http::get('https://maps.googleapis.com/maps/api/place/autocomplete/json', [
            'input' => $request->input,
            'key' => $apiKey,
            'types' => '(cities)', // Csak városok
            'components' => 'country:hu', // Csak Magyarország
            'language' => 'hu'
        ]);

        if ($response->failed()) {
            return response()->json([
                'error' => 'Hiba a Google Places API hívásnál'
            ], 500);
        }

        $data = $response->json();
        
        // Formázott eredmények
        $suggestions = collect($data['predictions'] ?? [])->map(function ($prediction) {
            return [
                'place_id' => $prediction['place_id'],
                'description' => $prediction['description'],
                'main_text' => $prediction['structured_formatting']['main_text'] ?? '',
                'secondary_text' => $prediction['structured_formatting']['secondary_text'] ?? '',
            ];
        });

        return response()->json([
            'suggestions' => $suggestions
        ]);
    }

    /**
     * Hely részletek lekérése place_id alapján (GPS koordináták)
     */
    public function getPlaceDetails(Request $request)
    {
        $request->validate([
            'place_id' => 'required|string'
        ]);

        $apiKey = env('GOOGLE_PLACES_API_KEY');
        
        if (!$apiKey) {
            return response()->json([
                'error' => 'Google Places API key nincs beállítva'
            ], 500);
        }

        // Google Places Details API hívás
        $response = Http::get('https://maps.googleapis.com/maps/api/place/details/json', [
            'place_id' => $request->place_id,
            'key' => $apiKey,
            'fields' => 'geometry,name,formatted_address',
            'language' => 'hu'
        ]);

        if ($response->failed()) {
            return response()->json([
                'error' => 'Hiba a Google Places API hívásnál'
            ], 500);
        }

        $data = $response->json();
        $result = $data['result'] ?? null;

        if (!$result) {
            return response()->json([
                'error' => 'Hely nem található'
            ], 404);
        }

        return response()->json([
            'name' => $result['name'],
            'address' => $result['formatted_address'],
            'latitude' => $result['geometry']['location']['lat'],
            'longitude' => $result['geometry']['location']['lng']
        ]);
    }

    /**
     * Közeli helyek keresése GPS koordináták alapján
     */
    public function nearbySearch(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'nullable|integer|min:1000|max:50000' // 1-50 km
        ]);

        $apiKey = env('GOOGLE_PLACES_API_KEY');
        
        if (!$apiKey) {
            return response()->json([
                'error' => 'Google Places API key nincs beállítva'
            ], 500);
        }

        $radius = $request->radius ?? 10000; // alapértelmezett 10 km

        // Google Places Nearby Search API hívás
        $response = Http::get('https://maps.googleapis.com/maps/api/place/nearbysearch/json', [
            'location' => $request->latitude . ',' . $request->longitude,
            'radius' => $radius,
            'key' => $apiKey,
            'type' => 'locality', // városok
            'language' => 'hu'
        ]);

        if ($response->failed()) {
            return response()->json([
                'error' => 'Hiba a Google Places API hívásnál'
            ], 500);
        }

        $data = $response->json();
        
        // Formázott eredmények
        $places = collect($data['results'] ?? [])->map(function ($place) use ($request) {
            $distance = $this->calculateDistance(
                $request->latitude,
                $request->longitude,
                $place['geometry']['location']['lat'],
                $place['geometry']['location']['lng']
            );

            return [
                'place_id' => $place['place_id'],
                'name' => $place['name'],
                'address' => $place['vicinity'] ?? '',
                'latitude' => $place['geometry']['location']['lat'],
                'longitude' => $place['geometry']['location']['lng'],
                'distance_km' => round($distance, 2)
            ];
        })->sortBy('distance_km')->values();

        return response()->json([
            'places' => $places
        ]);
    }

    /**
     * Két GPS koordináta közötti távolság számítása (Haversine formula)
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
