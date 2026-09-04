<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;

/**
 * PageController (Public)
 * ---------------------------------------------------------------------
 * Halaman publik Fase 1 — Beranda, Tentang Kami, Layanan, Karir, Kontak.
 * Semua tanpa login (route di luar middleware auth). Konten masih
 * hardcode di Blade; jadi dinamis (CMS ringan) baru Fase 12.
 *
 * Karir sengaja tampil "belum ada lowongan" — data pipeline lowongan
 * beneran baru aktif Fase 3 (Rekrutmen).
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
        // TODO Fase 3: ganti array kosong ini dengan data lowongan asli
        // dari tabel `job_openings`, filter status = 'open'.
        $openings = [];

        return view('public.careers', ['openings' => $openings]);
    }

    public function contact()
    {
        return view('public.contact');
    }

    public function storeContact(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        // TODO Fase 1.x: simpan ke tabel `contact_messages` dan/atau kirim
        // notifikasi email ke tim — untuk sekarang cuma flash message,
        // pesan TIDAK tersimpan di mana pun.
        return back()->with('contactSent', true);
    }
}