<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;

/**
 * DeployHookController
 * ---------------------------------------------------------------------
 * Endpoint rahasia yang dipanggil GitHub Actions lewat curl setelah
 * upload file selesai. Menjalankan tugas yang di server tanpa
 * terminal/SSH tidak bisa dijalankan manual: migrate + refresh cache.
 *
 * Keamanan: dilindungi token panjang di header X-Deploy-Token, DIBANDINGKAN
 * pakai hash_equals (bukan ==) supaya tidak bocor lewat timing attack.
 * Token disimpan di .env server (DEPLOY_HOOK_TOKEN) dan di GitHub Secrets
 * (nilainya harus SAMA persis). Rute ini dikecualikan dari CSRF di
 * bootstrap/app.php karena dipanggil tanpa sesi browser.
 * ---------------------------------------------------------------------
 */
class DeployHookController extends Controller
{
    public function handle(Request $request): Response
    {
        $expected = (string) config('services.deploy_hook.token');
        $given = (string) $request->header('X-Deploy-Token', '');

        if ($expected === '' || ! hash_equals($expected, $given)) {
            abort(403);
        }

        Artisan::call('migrate', ['--force' => true]);
        $migrateOutput = Artisan::output();

        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');
        Artisan::call('event:cache');

        return response(
            "migrate:\n{$migrateOutput}\ncache: config+route+view+event OK\n",
            200,
        )->header('Content-Type', 'text/plain');
    }
}