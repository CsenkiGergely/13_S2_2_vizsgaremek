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
     * Kép(ek) feltöltése kempinghez
     * - Csak a kemping tulajdonosa tölthet fel
     * - Max 10 kép / kemping
     * - Max 5 MB / kép, max 4000x4000 px
     * - Formátumok: jpg, jpeg, png, webp
     * - Két verzió: full (max 1920x1080) + thumb (600px széles, _thumb suffix)
     * - Több kép egyszerre: ha egy hibás, a többi még feltöltődik
     */
    public function upload(Request $request, $campingId)
    {
        $user = Auth::user();
        $camping = Camping::findOrFail($campingId);

        // Tulajdonos ellenőrzés (int cast a biztonságos összehasonlításhoz)
        if ((int) $camping->user_id !== (int) $user->id) {
            return response()->json([
                'message' => 'Csak a kemping tulajdonosa tölthet fel képeket.'
            ], 403);
        }

        $currentPhotosCount = CampingPhoto::where('camping_id', $campingId)->count();

        // Ha már elérte a limitet
        if ($currentPhotosCount >= 10) {
            return response()->json([
                'message' => 'Maximum 10 kép tölthető fel egy kempinghez.'
            ], 422);
        }

        // Több fájl is jöhet: photos[] VAGY egyetlen photo
        $files = $request->file('photos', []);
        if ($request->hasFile('photo')) {
            $files = array_merge($files, [$request->file('photo')]);
        }

        if (empty($files)) {
            return response()->json(['message' => 'Nincs fájl csatolva.'], 400);
        }

        $success = [];
        $errors = [];
        $uploaded = 0;

        foreach ($files as $index => $file) {
            $fileName = $file->getClientOriginalName();

            // --- Limit ellenőrzés ---
            if ($currentPhotosCount + $uploaded >= 10) {
                $errors[] = ['file' => $fileName, 'message' => 'Elérted a 10 képes limitet.'];
                continue;
            }

            // --- Fájlméret: max 5 MB ---
            if ($file->getSize() > 5 * 1024 * 1024) {
                $errors[] = ['file' => $fileName, 'message' => 'A fájl mérete meghaladja az 5 MB-ot.'];
                continue;
            }

            // --- Formátum ellenőrzés ---
            $ext = strtolower($file->getClientOriginalExtension());
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $errors[] = ['file' => $fileName, 'message' => 'Nem támogatott formátum. Engedélyezett: jpg, jpeg, png, webp.'];
                continue;
            }

            // --- Felbontás ellenőrzés: max 4000x4000 ---
            $imageSize = @getimagesize($file->getPathname());
            if (!$imageSize) {
                $errors[] = ['file' => $fileName, 'message' => 'Nem sikerült beolvasni a képet.'];
                continue;
            }
            [$origW, $origH] = $imageSize;
            if ($origW > 4000 || $origH > 4000) {
                $errors[] = ['file' => $fileName, 'message' => "A kép felbontása ({$origW}x{$origH}) meghaladja a 4000x4000 px limitet."];
                continue;
            }

            // --- GD: kép beolvasás ---
            $srcImage = $this->gdCreateFromFile($file->getPathname(), $ext);
            if (!$srcImage) {
                $errors[] = ['file' => $fileName, 'message' => 'Nem sikerült feldolgozni a képet.'];
                continue;
            }

            // --- Fájlnév generálás ---
            $baseName = time() . '_' . uniqid();
            $fullName = $baseName . '.' . $ext;        // pl. 1711234567_abc123.jpg
            $thumbName = $baseName . '_thumb.' . $ext;  // pl. 1711234567_abc123_thumb.jpg

            try {
                // --- Full kép: max 1920x1080, arányosan ---
                $fullImage = $this->gdResize($srcImage, $origW, $origH, 1920, 1080);
                $fullTmp = tempnam(sys_get_temp_dir(), 'full');
                $this->gdSave($fullImage, $fullTmp, $ext);
                imagedestroy($fullImage);

                // --- Thumb: 600px széles, arányos magasság ---
                $thumbH = (int) round($origH * (600 / $origW));
                $thumbImage = $this->gdResize($srcImage, $origW, $origH, 600, $thumbH);
                $thumbTmp = tempnam(sys_get_temp_dir(), 'thumb');
                $this->gdSave($thumbImage, $thumbTmp, $ext);
                imagedestroy($thumbImage);
                imagedestroy($srcImage);

                // --- S3 feltöltés ---
                $fullContent = file_get_contents($fullTmp);
                $thumbContent = file_get_contents($thumbTmp);

                if ($fullContent === false || $thumbContent === false) {
                    throw new \Exception('Nem sikerült a temp fájl beolvasása.');
                }

                \Log::info("S3 feltöltés indul: campings/{$fullName} (" . strlen($fullContent) . " bytes)");

                $fullOk = Storage::disk('s3')->put('campings/' . $fullName, $fullContent);
                $thumbOk = Storage::disk('s3')->put('campings/' . $thumbName, $thumbContent);

                \Log::info("S3 feltöltés eredmény - full: " . var_export($fullOk, true) . ", thumb: " . var_export($thumbOk, true));

                if (!$fullOk || !$thumbOk) {
                    throw new \Exception('S3 feltöltés sikertelen.');
                }

                // Temp fájlok törlése
                @unlink($fullTmp);
                @unlink($thumbTmp);

                // S3 URL összeállítás
                $bucketUrl = rtrim(config('filesystems.disks.s3.url'), '/');
                $fullUrl = $bucketUrl . '/campings/' . $fullName;

                // DB rekord (csak a full URL-t tároljuk, a thumb a névből kiszámolható)
                $photo = CampingPhoto::create([
                    'camping_id' => $camping->id,
                    'photo_url' => $fullUrl,
                    'caption' => $request->caption,
                ]);

                $uploaded++;
                $success[] = [
                    'id' => $photo->photo_id,
                    'photo_url' => $fullUrl,
                    'thumbnail_url' => $bucketUrl . '/campings/' . $thumbName,
                ];
            } catch (\Exception $e) {
                \Log::error('Képfeltöltés hiba: ' . $e->getMessage());
                $errors[] = ['file' => $fileName, 'message' => 'Szerverhiba a feltöltés közben.'];
                if (isset($srcImage) && is_resource($srcImage)) {
                    imagedestroy($srcImage);
                }
            }
        }

        $status = !empty($success) ? 201 : 422;

        return response()->json([
            'message' => !empty($success)
                ? $uploaded . ' kép sikeresen feltöltve.'
                : 'Egy kép sem töltődött fel.',
            'success' => $success,
            'errors' => $errors,
            'remaining_slots' => 10 - ($currentPhotosCount + $uploaded),
        ], $status);
    }

    // --- GD segédfüggvények ---

    /** Kép betöltése GD-vel a formátum alapján */
    private function gdCreateFromFile(string $path, string $ext)
    {
        return match ($ext) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($path),
            'png' => @imagecreatefrompng($path),
            'webp' => @imagecreatefromwebp($path),
            default => false,
        };
    }

    /** Arányos átméretezés — csak kicsinyít, nem nagyít */
    private function gdResize($srcImage, int $srcW, int $srcH, int $maxW, int $maxH)
    {
        // Ha a kép kisebb mint a max, nem nyújtjuk meg
        if ($srcW <= $maxW && $srcH <= $maxH) {
            // Másolatot adunk vissza
            $copy = imagecreatetruecolor($srcW, $srcH);
            $this->gdPreserveAlpha($copy);
            imagecopy($copy, $srcImage, 0, 0, 0, 0, $srcW, $srcH);
            return $copy;
        }

        // Arány megtartása
        $ratio = min($maxW / $srcW, $maxH / $srcH);
        $newW = (int) round($srcW * $ratio);
        $newH = (int) round($srcH * $ratio);

        $dst = imagecreatetruecolor($newW, $newH);
        $this->gdPreserveAlpha($dst);
        imagecopyresampled($dst, $srcImage, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);

        return $dst;
    }

    /** PNG/WebP alfa csatorna megőrzése */
    private function gdPreserveAlpha($image): void
    {
        imagealphablending($image, false);
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $transparent);
    }

    /** GD kép mentése temp fájlba a formátum alapján */
    private function gdSave($image, string $path, string $ext): void
    {
        match ($ext) {
            'jpg', 'jpeg' => imagejpeg($image, $path, 85),
            'png' => imagepng($image, $path, 8),
            'webp' => imagewebp($image, $path, 85),
        };
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

        // Tulajdonos ellenőrzés (int cast)
        if ((int) $camping->user_id !== (int) $user->id) {
            return response()->json([
                'message' => 'Csak a kemping tulajdonosa törölhet képeket.'
            ], 403);
        }

        $photo = CampingPhoto::where('camping_id', $campingId)
            ->where('photo_id', $photoId)
            ->firstOrFail();

        $bucketUrl = rtrim(config('filesystems.disks.s3.url'), '/') . '/';

        // S3 fájlok törlése (full + thumb)
        if (str_starts_with($photo->photo_url, 'http')) {
            $s3Key = str_replace($bucketUrl, '', $photo->photo_url);
            // Thumb key: pl. campings/abc.jpg → campings/abc_thumb.jpg
            $thumbKey = preg_replace('/\.(\w+)$/', '_thumb.$1', $s3Key);

            Storage::disk('s3')->delete([$s3Key, $thumbKey]);
        }

        // Régi lokális képek törlése (ha vannak)
        if (str_starts_with($photo->photo_url, '/storage/')) {
            $localPath = str_replace('/storage/', '', $photo->photo_url);
            Storage::disk('public')->delete($localPath);
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

        // Tulajdonos ellenőrzés (int cast)
        if ((int) $camping->user_id !== (int) $user->id) {
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

        // Tulajdonos ellenőrzés (int cast)
        if ((int) $camping->user_id !== (int) Auth::id()) {
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
