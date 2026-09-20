<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * files:privatize
 * ---------------------------------------------------------------------
 * Pindahkan file sensitif LAMA (kontrak, dokumen legal, selfie absensi)
 * dari disk publik (`storage/app/public`) ke disk private
 * (`storage/app/private`). Path di database TIDAK berubah, jadi cukup
 * dijalankan sekali di mesin lokal sebelum upload ke hosting. Aman
 * dijalankan berulang; `--dry-run` hanya menampilkan rencana.
 */
Artisan::command('files:privatize {--dry-run : Tampilkan saja tanpa memindahkan}', function () {
    $public = Storage::disk(\App\Support\PrivateFile::LEGACY_DISK);
    $private = Storage::disk(\App\Support\PrivateFile::DISK);
    $dry = (bool) $this->option('dry-run');

    $moved = 0;
    $skipped = 0;

    foreach (\App\Support\PrivateFile::MANAGED_DIRECTORIES as $directory) {
        foreach ($public->allFiles($directory) as $path) {
            if ($private->exists($path)) {
                $this->warn("Lewati (sudah ada di private): {$path}");
                $skipped++;
                continue;
            }

            $this->line(($dry ? '[dry-run] ' : '') . "Pindah: {$path}");

            if (! $dry) {
                $private->put($path, $public->get($path));
                $public->delete($path);
            }

            $moved++;
        }
    }

    $this->info(($dry ? 'Akan dipindah: ' : 'Dipindah: ') . "{$moved} file, dilewati: {$skipped}.");
})->purpose('Pindahkan kontrak, dokumen legal, dan selfie absensi lama dari disk publik ke disk private');