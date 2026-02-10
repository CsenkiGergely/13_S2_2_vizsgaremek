<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{

    // Új felhasználó regisztráció
    public function register(Request $request)
    {
        $fields = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed'
        ]);

        error_log("validáció sikeres");
        $user = User::create($fields);
        error_log("szerverfül elkészült");
        $token = $user->createToken($request->name);

        return ['user' => $user, 'token' => $token];
    }

    // Felhasználó bejelentkezés
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return [
                'message' => 'Invalid credentials'
            ];
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    // Felhasználó kijelentkezés
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return ['message' => 'Logged out'];
    }

    // Jelszó visszaállítási kérelem
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'message' => 'Nem regisztrált vagy nem megfelelő email.'
            ], 404);
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

        Mail::send('emails.reset-password', ['resetUrl' => $resetUrl, 'user' => $user], function($message) use ($request) {
            $message->to($request->email);
            $message->subject('Jelszó Visszaállítás - CampSite');
        });

        return response()->json([
            'message' => 'Jelszó visszaállító linket elküldtük az email címedre.'
        ], 200);
    }

    // Jelszó átállítása
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|min:8|confirmed'
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

        // token lejárat ellenőrzése 60p                       carbon atrakni datetime ra és a db t laravel lekeresre 
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
}
