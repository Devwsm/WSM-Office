<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\UpdateLandingContentRequest;
use App\Models\AuditLog;
use App\Models\OfficeSetting;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * LandingContentController (Owner/Developer)
 * ---------------------------------------------------------------------
 * Editor beranda publik — padanan "Landing Copy & Banner" di Settings
 * prototype (saveLandingSettingsV23). Isinya disimpan sebagai JSON di
 * `office_settings.landing_content` (singleton yang sama dengan Pengaturan
 * Kantor), form-nya ditaruh di halaman Pengaturan Kantor.
 * "Kembalikan bawaan" cukup mengosongkan kolom itu.
 * ---------------------------------------------------------------------
 */
class LandingContentController extends Controller
{
    public function update(UpdateLandingContentRequest $request)
    {
        $data = $request->validated();

        $content = [
            'headline' => trim($data['headline']),
            'tagline' => trim($data['tagline']),
            'cards' => collect($data['cards'])->values()->map(fn(array $card) => [
                'label' => trim($card['label']),
                'title' => trim($card['title']),
                'color' => strtolower($card['color']),
            ])->all(),
        ];

        $setting = OfficeSetting::query()->firstOrNew(['id' => 1]);
        $setting->landing_content = $content;
        $setting->save();

        $this->audit('Beranda publik diubah');

        return back()->with('status', 'Beranda publik berhasil disimpan.');
    }

    public function reset()
    {
        $setting = OfficeSetting::query()->first();

        if ($setting) {
            $setting->landing_content = null;
            $setting->save();
            $this->audit('Beranda publik dikembalikan ke bawaan');
        }

        return back()->with('status', 'Beranda publik dikembalikan ke teks bawaan.');
    }

    private function audit(string $action): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        AuditLog::record($action, "{$action} oleh {$actor->name}.", $actor);
    }
}