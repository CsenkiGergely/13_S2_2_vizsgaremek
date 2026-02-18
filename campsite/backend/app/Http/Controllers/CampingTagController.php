<?php

namespace App\Http\Controllers;

use App\Models\CampingTag;
use App\Models\Camping;
use Illuminate\Http\Request;

class CampingTagController extends Controller
{
    /**
     * Egy kemping összes tag-jének listázása (publikus)
     */
    public function index(string $campingId)
    {
        $camping = Camping::findOrFail($campingId);
        $tags = CampingTag::where('camping_id', $camping->id)->get();

        return response()->json($tags);
    }

    /**
     * Új tag hozzáadása egy kempinghez (csak tulajdonos)
     */
    public function store(Request $request, string $campingId)
    {
        $camping = Camping::where('user_id', $request->user()->id)
            ->findOrFail($campingId);

        $fields = $request->validate([
            'tag' => 'required|string|max:100',
        ]);

        // Ellenőrizzük, hogy a tag még nem létezik ennél a kempingnél
        $exists = CampingTag::where('camping_id', $camping->id)
            ->where('tag', $fields['tag'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Ez a tag már létezik ennél a kempingnél.'
            ], 422);
        }

        $tag = CampingTag::create([
            'camping_id' => $camping->id,
            'tag' => $fields['tag'],
        ]);

        return response()->json($tag, 201);
    }

    /**
     * Tag törlése (csak tulajdonos)
     */
    public function destroy(Request $request, string $campingId, string $tagId)
    {
        $camping = Camping::where('user_id', $request->user()->id)
            ->findOrFail($campingId);

        $tag = CampingTag::where('camping_id', $camping->id)
            ->findOrFail($tagId);

        $tag->delete();

        return response()->json(['message' => 'Tag törölve.']);
    }
}
