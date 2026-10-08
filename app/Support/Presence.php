<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Presence
 * ---------------------------------------------------------------------
 * Logika Monitor Login: menghitung status Online/Idle/Offline dari
 * `users.last_seen_*`, menerjemahkan nama route ke label halaman, dan
 * mencatat aktivitas.
 *
 * Pencatatan memakai query builder (toBase) SENGAJA supaya `updated_at`
 * user tidak ikut berubah — `updated_at` dipakai sebagai cache-buster URL
 * foto profil (User::avatarUrl()).
 * ---------------------------------------------------------------------
 */
class Presence
{
    public const ONLINE = 'online';
    public const IDLE = 'idle';
    public const OFFLINE = 'offline';

    /** Jangan tulis ke DB lebih sering dari ini kalau halamannya sama. */
    private const WRITE_THROTTLE_SECONDS = 20;

    /** Label halaman dari nama route; null kalau route harus diabaikan. */
    public static function labelFor(?string $routeName): ?string
    {
        if (! $routeName) {
            return null;
        }

        foreach (config('presence.ignore', []) as $pattern) {
            if (Str::is($pattern, $routeName)) {
                return null;
            }
        }

        foreach (config('presence.pages', []) as $pattern => $label) {
            if (Str::is($pattern, $routeName)) {
                return $label;
            }
        }

        return (string) config('presence.fallback_label', 'Halaman lain');
    }

    public static function status(User $user, ?Carbon $now = null): string
    {
        $now ??= Carbon::now();
        $seen = $user->last_seen_at;

        if (! $seen) {
            return self::OFFLINE;
        }

        // Sudah logout setelah aktivitas terakhir -> langsung Offline.
        if ($user->last_logout_at && $user->last_logout_at->greaterThanOrEqualTo($seen)) {
            return self::OFFLINE;
        }

        $age = $seen->diffInSeconds($now, true);

        if ($age <= (int) config('presence.online_seconds', 180)) {
            return self::ONLINE;
        }

        if ($age <= (int) config('presence.idle_seconds', 900)) {
            return self::IDLE;
        }

        return self::OFFLINE;
    }

    /** Catat user membuka halaman (dipanggil middleware TrackPresence). */
    public static function recordPage(User $user, string $routeName, string $label): void
    {
        $now = Carbon::now();

        if (
            $user->last_seen_at
            && $user->last_seen_route === $routeName
            && $user->last_seen_at->diffInSeconds($now, true) < self::WRITE_THROTTLE_SECONDS
        ) {
            return;
        }

        self::write($user, [
            'last_seen_at' => $now,
            'last_seen_route' => $routeName,
            'last_seen_label' => $label,
        ]);
    }

    /** Heartbeat: hanya memperbarui waktu, halaman terakhir tidak diubah. */
    public static function recordHeartbeat(User $user): void
    {
        self::write($user, ['last_seen_at' => Carbon::now()]);
    }

    public static function recordLogin(User $user): void
    {
        $now = Carbon::now();

        self::write($user, ['last_login_at' => $now, 'last_seen_at' => $now]);
    }

    public static function recordLogout(User $user): void
    {
        self::write($user, ['last_logout_at' => Carbon::now()]);
    }

    /** @param  array<string,mixed>  $values */
    private static function write(User $user, array $values): void
    {
        User::withTrashed()->whereKey($user->getKey())->toBase()->update($values);

        // Sinkronkan instance di memori (tanpa menandai dirty / menyentuh updated_at).
        $user->setRawAttributes(array_merge($user->getAttributes(), $values), true);
    }
}