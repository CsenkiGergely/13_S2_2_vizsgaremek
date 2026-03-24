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

        // Kép mentése az AWS S3 bucket-be
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');

            // Egyedi fájlnév generálása (időbélyeg + random ID + kiterjesztés)
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            // Feltöltés az S3 'campings/' mappába
            $path = $file->storeAs('campings', $filename, 's3');

            // Debug: ellenőrizzük, hogy a feltöltés sikeres volt-e
            \Log::info('S3 upload result - path: ' . var_export($path, true));
            \Log::info('S3 upload - filename: ' . $filename);

            // Ha a storeAs false-t ad vissza, a feltöltés sikertelen volt
            if ($path === false) {
                return response()->json(['message' => 'Hiba történt a kép feltöltése során az S3-ra.'], 500);
            }

            // A teljes publikus S3 URL manuális összeállítása
            // config() mindig működik, env() cache után nem - ezért config()-ot használunk
            // Pl.: https://cmpst-amzn-s3.s3.eu-north-1.amazonaws.com/campings/1234_abc.jpg
            $bucketUrl = rtrim(config('filesystems.disks.s3.url'), '/');
            $fullUrl = $bucketUrl . '/campings/' . $filename;

            \Log::info('S3 full URL: ' . $fullUrl);

            // CampingPhoto rekord létrehozása az adatbázisban
            $photo = CampingPhoto::create([
                'camping_id' => $camping->id,
                'photo_url' => $fullUrl, // Teljes S3 URL kerül mentésre
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

        // Fájl törlése az S3 bucket-ből
        // A photo_url teljes URL (pl. https://cmpst-amzn-s3.s3.eu-north-1.amazonaws.com/campings/abc.jpg)
        // Ebből kinyerjük az S3 key-t (pl. campings/abc.jpg) a bucket URL eltávolításával
        $bucketUrl = rtrim(config('filesystems.disks.s3.url'), '/') . '/';
        $s3Key = str_replace($bucketUrl, '', $photo->photo_url);

        // Ha az S3 key érvényes, töröljük a fájlt a bucket-ből
        if ($s3Key && Storage::disk('s3')->exists($s3Key)) {
            Storage::disk('s3')->delete($s3Key);
        }

        // Ha régi local storage kép volt (/storage/campings/...), azt is megpróbáljuk törölni
        if (str_starts_with($photo->photo_url, '/storage/')) {
            $localPath = str_replace('/storage/', '', $photo->photo_url);
            if (Storage::disk('public')->exists($localPath)) {
                Storage::disk('public')->delete($localPath);
            }
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
     
    /**
     * Fő kép beállítása
     * A kiválasztott kép adatait megcseréli az első (legkisebb photo_id-jú) kép adataival
     * Így a legkisebb ID-jú rekord mindig a fő kép marad
     */
    public function setMain($campingId, $photoId)
    {
        $user = Auth::user();
        $camping = Camping::findOrFail($campingId);

        if ($camping->user_id !== $user->id) {
            return response()->json([
                'message' => 'Csak a kemping tulajdonosa állíthat be fő képet.'
            ], 403);
        }

        // Az első kép (legkisebb photo_id) = jelenlegi fő kép
        $firstPhoto = CampingPhoto::where('camping_id', $campingId)
            ->orderBy('photo_id', 'asc')
            ->first();

        // A kiválasztott kép, amit fő képnek akarunk
        $selectedPhoto = CampingPhoto::where('camping_id', $campingId)
            ->where('photo_id', $photoId)
            ->firstOrFail();

        // Ha már ez az első, nincs teendő
        if ($firstPhoto->photo_id === $selectedPhoto->photo_id) {
            return response()->json(['message' => 'Ez már a fő kép.']);
        }

        // Adatok cseréje (URL + caption) a két rekord között
        $tempUrl = $firstPhoto->photo_url;
        $tempCaption = $firstPhoto->caption;

        $firstPhoto->update([
            'photo_url' => $selectedPhoto->photo_url,
            'caption' => $selectedPhoto->caption,
        ]);

        $selectedPhoto->update([
            'photo_url' => $tempUrl,
            'caption' => $tempCaption,
        ]);

        return response()->json([
            'message' => 'Fő kép sikeresen beállítva.',
            'main_photo' => $firstPhoto->fresh(),
        ]);
    }

    /**
     * Kép hozzáadása URL alapján (csak tulajdonos)
     */
    public function addByUrl(Request $request, $campingId)
    {
        $request->validate([
            'photo_url' => 'required|string|url',
            'caption' => 'nullable|string|max:255'
        ]);

        $camping = Camping::findOrFail($campingId);

        // Ellenőrizzük, hogy a user a kemping tulajdonosa-e
        if ($camping->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'Csak a kemping tulajdonosa adhat hozzá képeket.'
            ], 403);
        }

        // Fotó limit ellenőrzés
        $photoCount = CampingPhoto::where('camping_id', $camping->id)->count();
        if ($photoCount >= 10) {
            return response()->json([
                'message' => 'Maximum 10 kép tölthető fel egy kempinghez.'
            ], 422);
        }

        $photo = CampingPhoto::create([
            'camping_id' => $camping->id,
            'photo_url' => $request->photo_url,
            'caption' => $request->caption,
            'uploaded_at' => now()
        ]);

        return response()->json([
            'message' => 'Kép URL hozzáadva.',
            'photo' => $photo
        ], 201);
    }
}
