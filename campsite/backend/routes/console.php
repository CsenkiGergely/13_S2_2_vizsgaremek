<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('users:prune-unverified', function () {
    $ttlHours = (int) env('UNVERIFIED_ACCOUNT_TTL_HOURS', 24);
    $cutoff = now()->subHours($ttlHours);

    $users = User::query()
        ->whereNull('email_verified_at')
        ->where('created_at', '<', $cutoff)
        ->get(['id', 'email']);

    if ($users->isEmpty()) {
        $this->info('Nincs torolheto, nem megerositett fiok.');
        return;
    }

    DB::transaction(function () use ($users) {
        foreach ($users as $user) {
            $user->tokens()->delete();
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            $user->delete();
        }
    });

    $this->info('Torolve: ' . $users->count() . ' nem megerositett fiok.');
})->purpose('Torli a lejart, nem megerositett fiokokat.');

Schedule::command('users:prune-unverified')->daily();

