<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Camping;

class SearchController extends Controller
{
    /**
     * Keresés kempingek között név, leírás és helyszín alapján
     */
    public function search(Request $request)
    {
        $query = $request->input('q');

        if (!$query) {
            return response()->json([]);
        }

        $results = Camping::with(['location', 'photos', 'tags'])
            ->where('camping_name', 'ILIKE', "%{$query}%")
            ->orWhere('description', 'ILIKE', "%{$query}%")
            ->orWhereHas('location', function ($q) use ($query) {
                $q->where('city', 'ILIKE', "%{$query}%");
            })
            ->get()
            ->map(function ($camping) {
                return [
                    'id' => $camping->id,
                    'name' => $camping->camping_name,
                    'description' => $camping->description,
                    'location' => $camping->location?->city,
                    'image' => $camping->photos->first()?->photo_url,
                    'tags' => $camping->tags->pluck('tag'),
                ];
            });

        return response()->json($results);
    }
}
