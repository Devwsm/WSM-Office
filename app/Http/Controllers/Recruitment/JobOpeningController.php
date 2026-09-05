<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\StoreJobOpeningRequest;
use App\Http\Requests\Recruitment\UpdateJobOpeningRequest;
use App\Models\JobOpening;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * JobOpeningController (Recruitment)
 * ---------------------------------------------------------------------
 * Fase 3 — CRUD lowongan oleh HRD/Owner (middleware role:hrd,owner di
 * routes/web.php). `slug` dibuat otomatis dari `title` sekali saat
 * dibuat dan tidak pernah berubah lagi (lihat method slugFrom()), biar
 * link publik /karir/{slug} yang sudah dibagikan tidak rusak kalau
 * judul lowongan diedit belakangan.
 *
 * Status draft/published/closed diatur langsung dari form edit (bukan
 * lewat tombol aksi terpisah) supaya tetap simpel — kalau nanti perlu
 * "buka/tutup cepat" dari index tanpa buka form edit, tinggal tambah
 * action ringkas semacam Owner\EmployeeController@restore.
 * ---------------------------------------------------------------------
 */
class JobOpeningController extends Controller
{
    public function index(Request $request)
    {
        $query = JobOpening::query()->withCount('applications')->latest();

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        $openings = $query->paginate(15)->withQueryString();

        return view('recruitment.openings.index', [
            'openings' => $openings,
            'filters' => $request->only(['status']),
        ]);
    }

    public function create()
    {
        return view('recruitment.openings.create');
    }

    public function store(StoreJobOpeningRequest $request)
    {
        $data = $request->validated();
        $data['slug'] = $this->uniqueSlugFrom($data['title']);
        $data['created_by'] = $request->user()->id;

        if ($data['status'] === 'published') {
            $data['published_at'] = now();
        }

        JobOpening::create($data);

        return redirect()->route('recruitment.openings.index')->with('status', 'Lowongan baru berhasil dibuat.');
    }

    public function edit(JobOpening $opening)
    {
        return view('recruitment.openings.edit', ['opening' => $opening]);
    }

    public function update(UpdateJobOpeningRequest $request, JobOpening $opening)
    {
        $data = $request->validated();

        // Slug sengaja tidak ikut di-update — lihat catatan di atas.
        if ($data['status'] === 'published' && $opening->status !== 'published') {
            $data['published_at'] = now();
        }
        if ($data['status'] === 'closed' && $opening->status !== 'closed') {
            $data['closed_at'] = now();
        }

        $opening->update($data);

        return redirect()->route('recruitment.openings.index')->with('status', 'Lowongan berhasil diperbarui.');
    }

    /** Buat slug unik dari judul, tambah -2/-3/dst kalau sudah ada yang sama. */
    private function uniqueSlugFrom(string $title): string
    {
        $base = Str::slug($title) ?: 'lowongan';
        $slug = $base;
        $suffix = 2;

        while (JobOpening::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}