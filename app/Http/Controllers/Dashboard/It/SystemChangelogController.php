<?php

namespace App\Http\Controllers\Dashboard\It;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\It\SystemChangelogRequest;
use App\Models\SystemChangelog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * SystemChangelogController (Dashboard > IT > System Changelog)
 * ---------------------------------------------------------------------
 * Fase 15 — CRUD SystemChangelog, padanan `saveCustomChangeLog()` di
 * prototype v18 (panel "Changelog" buat end-user Owner, BEDA dari
 * README repo ini yang buat tim dev). `modules` (dipisah koma) &
 * `changes` (1 baris = 1 bullet) di-split jadi array di sini SETELAH
 * validasi — sama persis behaviour prototype.
 *
 * Gate 'view'/'manage' modul 'it' — sama pola persis modul lain.
 * ---------------------------------------------------------------------
 */
class SystemChangelogController extends Controller
{
    public function index(Request $request)
    {
        $selectedStatus = $request->string('status')->toString() ?: null;

        $changelogs = SystemChangelog::query()
            ->with('creator')
            ->when($selectedStatus, fn($q) => $q->where('status', $selectedStatus))
            ->orderByDesc('release_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('dashboard.it.changelog.index', [
            'changelogs' => $changelogs,
            'selectedStatus' => $selectedStatus,
        ]);
    }

    public function create()
    {
        return view('dashboard.it.changelog.create');
    }

    public function store(SystemChangelogRequest $request)
    {
        $data = $request->validated();
        $data['modules'] = $this->splitCommaList($data['modules'] ?? '');
        $data['changes'] = $this->splitLines($data['changes']);
        $data['created_by'] = Auth::id();

        SystemChangelog::create($data);

        return redirect()->route('dashboard.it.changelog.index')->with('status', 'Changelog berhasil ditambahkan.');
    }

    public function edit(SystemChangelog $changelog)
    {
        return view('dashboard.it.changelog.edit', ['changelog' => $changelog]);
    }

    public function update(SystemChangelogRequest $request, SystemChangelog $changelog)
    {
        $data = $request->validated();
        $data['modules'] = $this->splitCommaList($data['modules'] ?? '');
        $data['changes'] = $this->splitLines($data['changes']);

        $changelog->update($data);

        return redirect()->route('dashboard.it.changelog.index')->with('status', 'Changelog berhasil diperbarui.');
    }

    public function destroy(SystemChangelog $changelog)
    {
        $changelog->delete();

        return back()->with('status', 'Changelog berhasil dihapus.');
    }

    /** "Absensi, Payroll, Legal" -> ['Absensi', 'Payroll', 'Legal'] — sama behaviour input `modules` prototype. */
    private function splitCommaList(string $value): array
    {
        return collect(explode(',', $value))
            ->map(fn($item) => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    /** 1 baris textarea = 1 bullet perubahan — sama behaviour input `changes` prototype. */
    private function splitLines(string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $value))
            ->map(fn($line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }
}