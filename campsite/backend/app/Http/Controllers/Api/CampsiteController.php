<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campsite;
use Illuminate\Http\Request;

class CampsiteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Campsite::query();

        // Szöveg keresés
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('location', 'ILIKE', "%{$search}%");
            });
        }

        // Ár szűrés
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Minimum értékelés
        if ($request->has('min_rating')) {
            $query->where('rating', '>=', $request->min_rating);
        }

        // Helyszín típus szűrés (JSON field)
        if ($request->has('location_types')) {
            $types = explode(',', $request->location_types);
            $query->where(function($q) use ($types) {
                foreach ($types as $type) {
                    $q->orWhereJsonContains('location_types', trim($type));
                }
            });
        }

        // Szolgáltatások szűrés (JSON field)
        if ($request->has('services')) {
            $services = explode(',', $request->services);
            foreach ($services as $service) {
                $query->whereJsonContains('tags', trim($service));
            }
        }

        $campsites = $query->orderBy('featured', 'desc')
                           ->orderBy('rating', 'desc')
                           ->get();

        return response()->json($campsites);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $campsite = Campsite::findOrFail($id);
        return response()->json($campsite);
    }
}
