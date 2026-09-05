<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreJobApplicationRequest;
use App\Models\JobOpening;
use Illuminate\Http\Request;

/**
 * PageController (Public)
 * ---------------------------------------------------------------------
 * Halaman publik — Beranda, Tentang Kami, Layanan, Karir, Kontak. Semua
 * tanpa login (route di luar middleware auth). Konten masih hardcode di
 * Blade; jadi dinamis (CMS ringan) baru Fase 12.
 *
 * Karir (Fase 3) sekarang menampilkan lowongan asli dari tabel
 * `job_openings` yang status-nya `published` — draft/closed sengaja
 * disembunyikan dari publik meski sudah ada datanya (dikelola HRD/Owner
 * lewat Recruitment\JobOpeningController).
 * ---------------------------------------------------------------------
 */
class PageController extends Controller
{
    public function home()
    {
        return view('public.home');
    }

    public function about()
    {
        return view('public.about');
    }

    public function services()
    {
        return view('public.services');
    }

    public function careers()
    {
        $openings = JobOpening::query()
            ->where('status', 'published')
            ->latest('published_at')
            ->get();

        return view('public.careers', ['openings' => $openings]);
    }

    public function careerShow(JobOpening $lowongan)
    {
        // Draft/closed tidak ditampilkan ke publik biar link internal
        // (mis. dibagikan HRD sebelum tayang) tidak bocor ke pelamar.
        abort_unless($lowongan->status === 'published', 404);

        return view('public.career-show', ['opening' => $lowongan]);
    }

    public function careerApply(StoreJobApplicationRequest $request, JobOpening $lowongan)
    {
        abort_unless($lowongan->status === 'published', 404);

        $lowongan->applications()->create($request->validated());

        return back()->with('status', 'Lamaran kamu berhasil terkirim. Tim kami akan menghubungi lewat email kalau lolos ke tahap berikutnya.');
    }

    public function contact()
    {
        return view('public.contact');
    }

    public function storeContact(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        // TODO Fase 1.x: simpan ke tabel `contact_messages` dan/atau kirim
        // notifikasi email ke tim — untuk sekarang cuma flash message,
        // pesan TIDAK tersimpan di mana pun.
        return back()->with('status', 'Pesan kamu terkirim. Tim kami akan segera menghubungi balik.');
    }
}