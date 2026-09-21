<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * PageGuide
 * ---------------------------------------------------------------------
 * Pencari panduan halaman dashboard (tombol "? Panduan" di layouts.app).
 * Isi panduan & peta route ada di config/page_guides.php — kelas ini
 * cuma mencocokkan route yang sedang dibuka ke panduan yang tepat dan
 * menyiapkan label akses milik user yang login.
 *
 * Kalau route tidak punya panduan, resolve() mengembalikan null dan
 * partial tidak merender tombol apa pun (bukan error).
 * ---------------------------------------------------------------------
 */
class PageGuide
{
    /**
     * @param  string|null  $routeName  nama route yang sedang dibuka
     * @param  User|null  $user  user yang login (buat label akses)
     * @param  string|null  $override  kunci panduan yang dipaksa view (opsional)
     * @return array{key:string,title:string,summary:string,sections:array<string,array<int,string|array{0:string,1:string}>>,access:array{label:string,tone:string}|null}|null
     */
    public static function resolve(?string $routeName, ?User $user = null, ?string $override = null): ?array
    {
        $key = $override ?: self::keyForRoute($routeName);

        if ($key === null) {
            return null;
        }

        $guide = config("page_guides.guides.{$key}");

        if (! is_array($guide) || empty($guide['title'])) {
            return null;
        }

        return [
            'key' => $key,
            'title' => (string) $guide['title'],
            'summary' => (string) ($guide['summary'] ?? ''),
            'sections' => $guide['sections'] ?? [],
            'access' => self::accessBadge($guide['access'] ?? null, $user),
        ];
    }

    /** Cocokkan nama route ke kunci panduan (pola `*` didukung, yang pertama cocok menang). */
    public static function keyForRoute(?string $routeName): ?string
    {
        if (! $routeName) {
            return null;
        }

        foreach (config('page_guides.routes', []) as $pattern => $key) {
            if (Str::is($pattern, $routeName)) {
                return $key;
            }
        }

        return null;
    }

    /**
     * 'owner' -> label statis "Khusus Owner"; nama modul -> label dinamis
     * sesuai level akses user (Manage/View); selain itu tidak ada label.
     *
     * @return array{label:string,tone:string}|null
     */
    private static function accessBadge(?string $access, ?User $user): ?array
    {
        if ($access === null || $access === '') {
            return null;
        }

        if ($access === 'owner') {
            return ['label' => 'Khusus Owner', 'tone' => 'gray'];
        }

        if ($user === null) {
            return null;
        }

        return match ($user->accessLevel($access)) {
            'manage' => ['label' => 'Akses kamu: Manage (bisa lihat & ubah data)', 'tone' => 'green'],
            'view' => ['label' => 'Akses kamu: View (hanya lihat)', 'tone' => 'blue'],
            default => null,
        };
    }
}