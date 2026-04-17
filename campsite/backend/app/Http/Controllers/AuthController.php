<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthController extends Controller
{

    /**
     * Felhasználó regisztrációja
     */
    public function register(Request $request)
    {
        $fields = $request->validate([
            'owner_first_name' => 'required|max:255',
            'owner_last_name' => 'required|max:255',
            'email' => 'required|email|unique:users',
            'password' => [
                'required',
                'confirmed',
                PasswordRule::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->uncompromised(),
            ]
        ], [
            'password.min' => 'A jelszónak legalább 8 karakter hosszúnak kell lennie.',
            'password.mixed' => 'A jelszónak tartalmaznia kell kis- és nagybetűt is.',
            'password.numbers' => 'A jelszónak tartalmaznia kell legalább egy számot.',
            'password.uncompromised' => 'Ez a jelszó szerepel ismert adatszivárgásokban. Kérjük válassz egy másik jelszót.',
            'password.confirmed' => 'A két jelszó nem egyezik meg.',
        ]);

        $user = User::create($fields);

        // Email verifikacio ideiglenesen kikapcsolva: azonnali aktiválás.
        $user->email_verified_at = now();
        $user->save();

        // API token generálása (Sanctum) azonnali belépéshez.
        $authToken = $user->createToken($request->owner_first_name)->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $authToken,
            'message' => 'Sikeres regisztráció! A fiókod azonnal aktiválva lett.'
        ], 201);
    }

    /**
     * Bejelentkezés
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Hibás email vagy jelszó.'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['user' => $user, 'token' => $token]);
    }

    /**
     * Kijelentkezés
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Kijelentkeztél.']);
    }

    // Jelszó visszaállítási kérelem
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $user = User::where('email', $request->email)->first();

        // Biztonsági okokból mindig ugyanazt a választ adjuk,
        // így nem áruljuk el hogy létezik-e a fiók
        if (!$user) {
            return response()->json([
                'message' => 'Ha ez az email cím regisztrálva van, elküldtük a visszaállító linket.'
            ]);
        }

        $token = Str::random(64);


        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'email' => $request->email,
                'token' => Hash::make($token),
                'created_at' => now()
            ]
        );

        $resetUrl = config('app.frontend_url', 'http://localhost:5173') . '/reset-password?token=' . $token . '&email=' . urlencode($request->email);

        try {
            Mail::send('emails.reset-password', ['resetUrl' => $resetUrl, 'user' => $user], function($message) use ($request) {
                $message->to($request->email);
                $message->subject('Jelszó Visszaállítás - CampSite');
            });
        } catch (\Exception $e) {
            Log::error('Email kuldesi hiba jelszo visszaallitasnal', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'message' => 'Ha ez az email cím regisztrálva van, elküldtük a visszaállító linket.'
        ]);
    }

    // Jelszó átállítása
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required',
            'password' => [
                'required',
                'confirmed',
                PasswordRule::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->uncompromised(),
            ]
        ], [
            'password.min' => 'A jelszónak legalább 8 karakter hosszúnak kell lennie.',
            'password.mixed' => 'A jelszónak tartalmaznia kell kis- és nagybetűt is.',
            'password.numbers' => 'A jelszónak tartalmaznia kell legalább egy számot.',
            'password.uncompromised' => 'Ez a jelszó szerepel ismert adatszivárgásokban. Kérjük válassz egy másik jelszót.',
            'password.confirmed' => 'A két jelszó nem egyezik meg.',
        ]);

        $passwordReset = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$passwordReset) {
            return response()->json([
                'message' => 'Érvénytelen token vagy email cím.'
            ], 400);
        }

        if (!Hash::check($request->token, $passwordReset->token)) {
            return response()->json([
                'message' => 'Érvénytelen vagy lejárt token.'
            ], 400);
        }

        // token lejárat ellenőrzése 60p                       
        $createdAt = \Carbon\Carbon::parse($passwordReset->created_at);
        if ($createdAt->addMinutes(60)->isPast()) {
            return response()->json([
                'message' => 'A token lejárt. Kérj új jelszó visszaállítási linket.'
            ], 400);
        }

        // Érvénytelen token vagy email cím
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'message' => 'Érvénytelen token vagy email cím.'
            ], 400);
        }
        $user->password = Hash::make($request->password);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'message' => 'Jelszó sikeresen megváltoztatva!'
        ], 200);
    }

    // partner státuszra váltás bejelentkezett felhasználónak
    public function upgradeToPartner(Request $request)
    {
        // -> nincs külön nincs jogosultságod üzenet
        // validáljuk a telefonszámot
        $fields = $request->validate([
            'phone_number' => 'required|string|max:20'
        ]);

        // bejelentkezett user adatainak frissítése
        $user = $request->user();
        $user->phone_number = $fields['phone_number'];
        $user->role = 1; // true
        $user->save();

        return response()->json([
            'message' => 'Sikeresen partner státuszra váltottál!',
            'user' => $user
        ], 200);
    }

    /**
     * Bejelentkezett felhasználó profil adatainak lekérdezése
     */
    public function profile(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Email cím megerősítése a regisztráció után
     */
    public function verifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Érvénytelen email cím.'
            ], 400);
        }

        // Ha már aktivált
        if ($user->email_verified_at) {
            return response()->json([
                'message' => 'Az email cím már meg van erősítve.'
            ]);
        }

        // Token ellenőrzése
        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record || !Hash::check($request->token, $record->token)) {
            return response()->json([
                'message' => 'Érvénytelen vagy lejárt aktiváló link.'
            ], 400);
        }

        // 24 óra lejárat
        $createdAt = \Carbon\Carbon::parse($record->created_at);
        if ($createdAt->addHours(24)->isPast()) {
            return response()->json([
                'message' => 'Az aktiváló link lejárt. Kérj újat!'
            ], 400);
        }

        // Email megerősítése
        $user->email_verified_at = now();
        $user->save();

        // Token törlése
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'message' => 'Email sikeresen megerősítve! Most már bejelentkezhetsz.'
        ]);
    }

    /**
     * Aktiváló email újraküldése
     */
    public function resendVerification(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Ha ez az email cím regisztrálva van, elküldtük az aktiváló linket.'
            ]);
        }

        if ($user->email_verified_at) {
            return response()->json([
                'message' => 'Az email cím már meg van erősítve.'
            ]);
        }

        // Új token
        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'email' => $user->email,
                'token' => Hash::make($token),
                'created_at' => now()
            ]
        );

        $verifyUrl = config('app.frontend_url', 'http://localhost:5173')
            . '/verify-email?token=' . $token
            . '&email=' . urlencode($user->email);

        try {
            Mail::send('emails.verify-email', ['verifyUrl' => $verifyUrl, 'user' => $user], function ($message) use ($user) {
                $message->to($user->email);
                $message->subject('Email Megerősítés - CampSite');
            });
        } catch (\Exception $e) {
            Log::error('Email kuldesi hiba megerosito email ujrakuldesnel', [
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);

            $mailMessage = strtolower($e->getMessage());
            if (str_contains($mailMessage, 'ms42225') || str_contains($mailMessage, 'unique recipients limit')) {
                return response()->json([
                    'message' => 'Az email szolgaltato trial limitet ert el, ezert most nem tudtuk ujrakuldeni az aktivalo emailt.'
                ], 503);
            }

            return response()->json([
                'message' => 'Az aktivalo email ujrakuldese jelenleg nem elerheto.'
            ], 503);
        }

        return response()->json([
            'message' => 'Ha ez az email cím regisztrálva van, elküldtük az aktiváló linket.'
        ]);
    }
}
