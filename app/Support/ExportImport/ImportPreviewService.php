<?php

namespace App\Support\ExportImport;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * ImportPreviewService
 * ---------------------------------------------------------------------
 * Batch 0 (fondasi Export & Import) — jembatan antara "upload file" dan
 * "klik Konfirmasi Import", dipakai SEMUA modul import (Batch 3: Work
 * Tracker duluan, nyusul Manajemen Karyawan/KPI/Budget).
 *
 * Alurnya sengaja 2 tahap terpisah (BUKAN langsung insert pas upload),
 * biar user bisa lihat dulu mana baris yang valid & mana yang error
 * SEBELUM data beneran masuk database:
 *
 *   1. Controller parse file upload pakai class turunan
 *      `App\Imports\BaseImport` (lihat class itu) -> dapat daftar baris
 *      valid & invalid -> panggil `stage()` di sini, simpan sementara
 *      pakai token acak.
 *   2. Halaman preview nampilin isi `retrieve()` (baris valid vs baris
 *      error) pakai token dari langkah 1 — TIDAK baca ulang file
 *      aslinya, jadi apa yang di-preview dijamin sama persis dengan
 *      apa yang bakal di-commit.
 *   3. User klik "Konfirmasi Import" -> controller `retrieve()` lagi
 *      pakai token yang sama, insert baris-baris valid-nya ke
 *      database, lalu `forget()` buat beresin cache-nya.
 *
 * Disimpan di cache (bukan session) supaya gak ke-bloat isi session,
 * dan otomatis kadaluarsa (30 menit) kalau user upload lalu
 * ditinggal begitu saja tanpa pernah konfirmasi.
 * ---------------------------------------------------------------------
 */
class ImportPreviewService
{
    private const TTL_MINUTES = 30;

    /**
     * Simpan hasil parsing sementara, kembalikan token buat dipakai
     * lagi di halaman preview & saat commit.
     *
     * @param  array{valid: array, invalid: array, headings: array}  $payload
     */
    public function stage(User $user, string $catalogKey, array $payload): string
    {
        $token = Str::random(24);

        Cache::put($this->cacheKey($user, $catalogKey, $token), $payload, now()->addMinutes(self::TTL_MINUTES));

        return $token;
    }

    /**
     * @return array{valid: array, invalid: array, headings: array}|null null kalau token gak ada/sudah kadaluarsa
     */
    public function retrieve(User $user, string $catalogKey, string $token): ?array
    {
        return Cache::get($this->cacheKey($user, $catalogKey, $token));
    }

    public function forget(User $user, string $catalogKey, string $token): void
    {
        Cache::forget($this->cacheKey($user, $catalogKey, $token));
    }

    private function cacheKey(User $user, string $catalogKey, string $token): string
    {
        return "import-preview:{$catalogKey}:{$user->id}:{$token}";
    }
}