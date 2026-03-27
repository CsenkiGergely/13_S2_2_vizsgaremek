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
     * Tagek szinkronizálása — a megadott lista lesz az aktuális (csak tulajdonos)
     */
    public function sync(Request $request, string $campingId)
    {
        $camping = Camping::where('user_id', $request->user()->id)
            ->findOrFail($campingId);

        $fields = $request->validate([
            'tags' => 'required|array',
            'tags.*' => 'string|max:100',
        ]);

        // Töröljük a régieket
        CampingTag::where('camping_id', $camping->id)->delete();

        // Újakat hozzáadjuk
        $tags = [];
        foreach (array_unique($fields['tags']) as $tagName) {
            $tags[] = CampingTag::create([
                'camping_id' => $camping->id,
                'tag' => $tagName,
            ]);
        }

        return response()->json($tags, 200);
    }
}
