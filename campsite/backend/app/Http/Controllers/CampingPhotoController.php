<?php

namespace App\Http\Controllers;

use App\Models\CampingPhoto;
use App\Models\Camping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class CampingPhotoController extends Controller
{
    /**
     * Kép feltöltése kempinghez
     * Csak a kemping tulajdonosa tölthet fel képet
     * Maximum 10 kép / kemping
     * Formátumok: jpg, jpeg, png, webp
     * Maximum fájlméret: 10MB
     */
    public function upload(Request $request, $campingId)
    {
        $user = Auth::user();
        $camping = Camping::findOrFail($campingId);

        // Ellenőrizzük, hogy a user a kemping tulajdonosa-e
        if ($camping->user_id !== $user->id) {
            return response()->json([
                'message' => 'Csak a kemping tulajdonosa tölthet fel képeket.'
            ], 403);
        }

        // Ellenőrizzük a képek számát
        $currentPhotosCount = CampingPhoto::where('camping_id', $campingId)->count();
        if ($currentPhotosCount >= 10) {
            return response()->json([
                'message' => 'Maximum 10 kép tölthető fel egy kempinghez. Kérjük, törölj néhány képet, mielőtt újat töltenél fel.'
            ], 422);
        }

        $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:10240', // max 10MB
            'caption' => 'nullable|string|max:255'
        ]);

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
                'photo' => $photo,
                'remaining_slots' => 10 - ($currentPhotosCount + 1)
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

   
     //Kép törlése
     
    public function destroy($campingId, $photoId)
    {
        $user = Auth::user();
        $camping = Camping::findOrFail($campingId);

        // Ellenőrizzük, hogy a user a kemping tulajdonosa-e
        if ($camping->user_id !== $user->id) {
            return response()->json([
                'message' => 'Csak a kemping tulajdonosa törölhet képeket.'
            ], 403);
        }

        $photo = CampingPhoto::where('camping_id', $campingId)
            ->where('photo_id', $photoId)
            ->firstOrFail();

        // Fájl törlése a storage-ból
        $filePath = str_replace('/storage/', '', $photo->photo_url);
        if (Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->delete($filePath);
        }

        $photo->delete();

        $remainingPhotos = CampingPhoto::where('camping_id', $campingId)->count();

        return response()->json([
            'message' => 'Kép sikeresen törölve',
            'remaining_photos' => $remainingPhotos,
            'remaining_slots' => 10 - $remainingPhotos
        ], 200);
    }

    
    //Kép URL frissítése (ha már létezik a képfájl)
     
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
