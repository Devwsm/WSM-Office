<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * PrivateFile
 * ---------------------------------------------------------------------
 * Satu-satunya pintu untuk file sensitif (kontrak karyawan, dokumen
 * legal, selfie absensi). Sebelumnya file-file ini disimpan di disk
 * `public` dan dilink lewat `asset('storage/...')`, artinya siapa pun
 * yang punya URL-nya bisa membuka file TANPA login.
 *
 * Sekarang:
 *  - File disimpan di disk `local` (`storage/app/private`), di luar
 *    document root dan tidak bisa dijangkau lewat URL langsung.
 *  - File hanya keluar lewat route yang dijaga middleware auth + akses
 *    modul (lihat `dashboard.contracts.file`, `dashboard.legal.file`,
 *    `attendance.recap.photo`), lalu dialirkan oleh `response()` di sini.
 *  - `response()` juga membaca disk `public` sebagai fallback KHUSUS
 *    file lama yang belum dipindah. Pindahkan dengan
 *    `php artisan files:privatize`; setelah itu fallback tidak terpakai.
 *
 * Tidak butuh `php artisan storage:link` (yang tidak bisa dijalankan di
 * shared hosting tanpa terminal).
 * ---------------------------------------------------------------------
 */
class PrivateFile
{
    public const DISK = 'local';

    /** Disk lama (publik) — hanya dibaca sebagai fallback & dibersihkan saat hapus. */
    public const LEGACY_DISK = 'public';

    /** Folder yang dulu tersimpan di disk publik dan harus dipindah. */
    public const MANAGED_DIRECTORIES = ['contracts', 'legal', 'attendance'];

    /** Tipe yang aman ditampilkan langsung di browser; sisanya dipaksa unduh. */
    private const INLINE_TYPES = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];

    /** Simpan upload ke disk private. Return path relatif untuk kolom `file_path`. */
    public static function store(UploadedFile $file, string $directory): string
    {
        return $file->store($directory, self::DISK);
    }

    /** Tulis konten mentah (mis. selfie hasil decode base64) ke disk private. */
    public static function put(string $path, string $contents): void
    {
        Storage::disk(self::DISK)->put($path, $contents);
    }

    /** Hapus file dari disk private DAN sisa salinan lamanya di disk publik. */
    public static function delete(?string $path): void
    {
        if (! self::isSafePath($path)) {
            return;
        }

        Storage::disk(self::DISK)->delete($path);
        Storage::disk(self::LEGACY_DISK)->delete($path);
    }

    /**
     * Alirkan file ke browser. 404 kalau file tidak ada di kedua disk.
     * Pemanggil WAJIB sudah memastikan user berhak (middleware/abort_unless).
     */
    public static function response(?string $path, ?string $downloadName = null): Response
    {
        $disk = self::diskHolding($path);
        abort_if($disk === null, 404, 'File tidak ditemukan.');

        $storage = Storage::disk($disk);
        $mime = $storage->mimeType($path) ?: 'application/octet-stream';
        $inline = in_array($mime, self::INLINE_TYPES, true);

        return $storage->response(
            $path,
            self::safeName($downloadName, $path),
            [
                'Content-Type' => $mime,
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store, max-age=0',
            ],
            $inline ? 'inline' : 'attachment',
        );
    }

    /** Nama disk yang menyimpan file ini, atau null kalau tidak ada. */
    public static function diskHolding(?string $path): ?string
    {
        if (! self::isSafePath($path)) {
            return null;
        }

        foreach ([self::DISK, self::LEGACY_DISK] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return $disk;
            }
        }

        return null;
    }

    /** Path berasal dari DB, tapi tetap tolak traversal & path absolut. */
    private static function isSafePath(?string $path): bool
    {
        return is_string($path)
            && $path !== ''
            && ! str_contains($path, '..')
            && ! str_starts_with($path, '/')
            && ! str_contains($path, "\0");
    }

    private static function safeName(?string $name, string $path): string
    {
        $name = trim((string) $name);
        $name = preg_replace('/[\\\\\/\r\n"]+/', '_', $name) ?? '';

        if ($name === '') {
            $name = basename($path);
        }

        // Nama asli dari user bisa tanpa ekstensi; pinjam ekstensi file tersimpan.
        if (pathinfo($name, PATHINFO_EXTENSION) === '' && ($ext = pathinfo($path, PATHINFO_EXTENSION)) !== '') {
            $name .= '.' . $ext;
        }

        return $name;
    }
}