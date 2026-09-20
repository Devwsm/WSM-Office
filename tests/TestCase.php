<?php

namespace Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Semua halaman memanggil @vite(...). Tanpa ini, tes hanya lolos kalau
        // `public/hot` (penanda dev-server) atau `public/build` kebetulan ada;
        // begitu `public/hot` dihapus untuk produksi, seluruh tes view akan
        // gagal dengan ViteManifestNotFoundException. Tes tidak butuh aset asli.
        $this->withoutVite();

        $this->emulateMysqlDateColumns();
        $this->registerMysqlFunctionsForSqlite();
    }

    /**
     * Aplikasi berjalan di MySQL/MariaDB, tempat kolom DATE otomatis membuang
     * jam ("2026-09-21 00:00:00" tersimpan sebagai "2026-09-21"). SQLite (yang
     * dipakai `phpunit.xml`) tidak punya tipe DATE, sehingga cast `date`
     * Eloquent tersimpan lengkap dengan jam dan query seperti
     * `where('date', '2026-09-21')` atau `whereBetween('date', [...])` tidak
     * cocok dengan barisnya. Listener ini meniru perilaku MySQL: setiap
     * atribut ber-cast `date` dipotong menjadi `Y-m-d` sebelum disimpan,
     * supaya hasil tes di SQLite sama dengan di produksi.
     */
    private function emulateMysqlDateColumns(): void
    {
        $this->app['events']->listen('eloquent.saving: *', function (string $event, array $payload): void {
            $model = $payload[0] ?? null;

            if (! $model instanceof Model) {
                return;
            }

            $attributes = $model->getAttributes();
            $changed = false;

            foreach ($model->getCasts() as $key => $cast) {
                if ($cast !== 'date') {
                    continue;
                }

                $raw = $attributes[$key] ?? null;

                if (is_string($raw) && strlen($raw) > 10) {
                    $attributes[$key] = substr($raw, 0, 10);
                    $changed = true;
                }
            }

            if ($changed) {
                $model->setRawAttributes($attributes);
            }
        });
    }

    /**
     * Beberapa query aplikasi memakai fungsi khusus MySQL (`FIELD()` untuk urutan
     * status KPI, `DATE_FORMAT()` untuk filter periode export cuti). SQLite tidak
     * punya keduanya, jadi didaftarkan di sini agar halaman yang memakainya bisa
     * dites di SQLite tanpa mengubah kode aplikasi. Di MySQL/MariaDB asli tidak
     * dipakai sama sekali.
     */
    private function registerMysqlFunctionsForSqlite(): void
    {
        $connection = $this->app['db']->connection();

        if ($connection->getDriverName() !== 'sqlite') {
            return;
        }

        $pdo = $connection->getPdo();

        $pdo->sqliteCreateFunction('FIELD', function ($needle, ...$haystack) {
            $index = array_search($needle, $haystack, false);

            return $index === false ? 0 : $index + 1;
        });

        $pdo->sqliteCreateFunction('DATE_FORMAT', function ($value, $format) {
            if ($value === null) {
                return null;
            }

            $map = ['%Y' => 'Y', '%m' => 'm', '%d' => 'd', '%H' => 'H', '%i' => 'i', '%s' => 's', '%y' => 'y', '%e' => 'j', '%c' => 'n'];

            return date(strtr($format, $map), strtotime($value));
        });
    }
}