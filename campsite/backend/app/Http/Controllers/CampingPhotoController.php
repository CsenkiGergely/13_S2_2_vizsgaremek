<?php

namespace App\Http\Controllers;

use App\Models\CampingPhoto;
use App\Models\Camping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CampingPhotoController extends Controller
{
    //kép feltöltése 

    public function upload(Request $request, $campingId)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // max 5MB
            'caption' => 'nullable|string|max:255'
        ]);

        $camping = Camping::findOrFail($campingId);

        // Kép mentése
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('campings', $filename, 'public');

            // CampingPhoto rekord létrehozása
            $photo = CampingPhoto::create([
                'camping_id' => $camping->id,
                'photo_url' => '/storage/' . $path,
                'caption' => $request->caption,
                'uploaded_at' => now()
            ]);

            return response()->json([
                'message' => 'Kép sikeresen feltöltve',
                'photo' => $photo
            ], 201);
        }

        return response()->json(['message' => 'Nincs fájl'], 400);
    }

    /**
     * Kemping összes képének lekérése
     * http://127.0.0.1:8000/api/campings/1/photos
     */
    public function index($campingId)
    {
        $camping = Camping::findOrFail($campingId);
        $photos = $camping->photos;

        return response()->json($photos);
    }

    /**
     * Kép törlése
     */
    public function destroy($campingId, $photoId)
    {
        $photo = CampingPhoto::where('camping_id', $campingId)
            ->where('id', $photoId)
            ->firstOrFail();

        // Fájl törlése a storage-ból
        $filePath = str_replace('/storage/', '', $photo->photo_url);
        Storage::disk('public')->delete($filePath);

        $photo->delete();

        return response()->json(['message' => 'Kép törölve'], 200);
    }

    /**
     * Kép URL frissítése (ha már létezik a képfájl)
     */
    public function addByUrl(Request $request, $campingId)
    {
        $request->validate([
            'photo_url' => 'required|string',
            'caption' => 'nullable|string|max:255'
        ]);

        $camping = Camping::findOrFail($campingId);

        $photo = CampingPhoto::create([
            'camping_id' => $camping->id,
            'photo_url' => $request->photo_url,
            'caption' => $request->caption,
            'uploaded_at' => now()
        ]);

        return response()->json([
            'message' => 'Kép URL hozzáadva',
            'photo' => $photo
        ], 201);
    }
}
