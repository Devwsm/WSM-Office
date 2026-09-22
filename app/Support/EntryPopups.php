<?php

namespace App\Support;

use App\Models\User;

/**
 * EntryPopups
 * ---------------------------------------------------------------------
 * Popup informasi preview (README Bab 4.2 no. 8, config/entry_popups.php,
 * resources/views/partials/entry-popups.blade.php). Kelas ini CUMA
 * menyiapkan data (teks sudah jadi + urutan popup per pintu) — antrean
 * tampil, sessionStorage, dan animasi ada di resources/js/entry-popups.js
 * & resources/css/app.css.
 *
 * Tiga pintu ('door'), tiap pintu urutan popupnya beda:
 * - 'public'    : [notice]          — 5 halaman publik + halaman login
 * - 'app'       : [welcome, notice] — App Mode (layouts.employee)
 * - 'dashboard' : [notice]          — Dashboard (layouts.app), teks beda tipis
 *
 * Kalau kedua saklar (`entry_popups.enabled` & `entry_popups.preview_mode`)
 * mati / popup-nya kosong, forDoor() balikin popups: [] dan partial
 * TIDAK merender apa pun (bukan error) — sama pola seperti
 * PageGuide::resolve() balikin null.
 * ---------------------------------------------------------------------
 */
class EntryPopups
{
    /**
     * @param  'public'|'app'|'dashboard'  $door
     * @return array{popups: array<int, array<string, mixed>>}
     */
    public static function forDoor(string $door, ?User $user): array
    {
        if (! config('entry_popups.enabled', true)) {
            return ['popups' => []];
        }

        $popups = [];

        // Sambutan cuma di App Mode, dan cuma kalau ada user yang login
        // (App Mode sendiri selalu butuh login, tapi jaga-jaga kalau
        // partial ini suatu saat di-include dari tempat lain).
        if ($door === 'app' && $user) {
            $popups[] = self::welcome($user);
        }

        if (config('entry_popups.preview_mode', true)) {
            $popups[] = self::notice($door);
        }

        return ['popups' => $popups];
    }

    /** @return array<string, mixed> */
    private static function welcome(User $user): array
    {
        $data = config('entry_popups.welcome');

        return [
            'type' => 'welcome',
            'key' => 'welcome',
            'version' => (int) config('entry_popups.version', 1),
            'greeting' => self::greeting(),
            'first_name' => explode(' ', $user->name)[0],
            'role_label' => $user->roleLabel(),
            'title' => (string) ($data['title'] ?? 'Welcome to W.O.S'),
            'subtitle' => (string) ($data['subtitle'] ?? ''),
            'button' => (string) ($data['button'] ?? 'Lanjut'),
        ];
    }

    /**
     * @param  'public'|'app'|'dashboard'  $door
     * @return array<string, mixed>
     */
    private static function notice(string $door): array
    {
        $data = config('entry_popups.notice');
        $body = $door === 'dashboard'
            ? ($data['dashboard_body'] ?? $data['body'] ?? [])
            : ($data['body'] ?? []);

        return [
            'type' => 'notice',
            'key' => "notice-{$door}",
            'version' => (int) config('entry_popups.version', 1),
            'title' => (string) ($data['title'] ?? ''),
            'body' => array_values($body),
            'button' => (string) ($data['button'] ?? 'Oke, mengerti'),
        ];
    }

    /** Sapaan menurut jam server (Asia/Jakarta, lihat config/app.php `timezone`). */
    private static function greeting(): string
    {
        $hour = now()->hour;

        return match (true) {
            $hour < 4 => 'Masih malam,',
            $hour < 11 => 'Selamat pagi,',
            $hour < 15 => 'Selamat siang,',
            $hour < 19 => 'Selamat sore,',
            default => 'Selamat malam,',
        };
    }
}