{{--
    public/home.blade.php — Beranda
    ---------------------------------------------------------------------
    Konten masih placeholder generik. Ganti teks/gambar begitu materi
    resmi dari tim (profil perusahaan, foto, dsb.) tersedia — nanti bisa
    diedit lewat CMS ringan di Fase 12, untuk sekarang edit langsung di
    file ini.
    ---------------------------------------------------------------------
--}}
@extends('layouts.public', ['title' => 'Beranda'])

@section('content')
    <section class="mx-auto max-w-6xl px-4 pb-10 pt-10 sm:px-6 sm:pb-16 sm:pt-14 lg:pt-20">
        <div class="grid gap-10 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
            <div>
                <span class="badge-wsm-blue">Whisnu Santika Music</span>
                <h1 class="mt-5 text-[32px] font-black leading-[1.02] tracking-tight sm:text-[44px] lg:text-[64px]">
                    Musik, karya, dan tim di baliknya.
                </h1>
                <p class="mt-5 max-w-lg text-[17px] text-muted">
                    WSM mengelola produksi musik, kampanye, dan operasional tim di balik karya-karya
                    Whisnu Santika — dari proses kreatif sampai ke publik.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('public.careers') }}" class="btn-wsm-black">Lihat Lowongan</a>
                    <a href="{{ route('public.about') }}" class="btn-wsm-white">Tentang Kami</a>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3.5">
                <div class="stat-wsm-blue">
                    <span class="stat-wsm-label">Fokus</span>
                    <strong class="stat-wsm-value">Musik</strong>
                </div>
                <div class="stat-wsm-yellow">
                    <span class="stat-wsm-label">Tim</span>
                    <strong class="stat-wsm-value">Kreatif</strong>
                </div>
                <div class="stat-wsm-green">
                    <span class="stat-wsm-label">Kampanye</span>
                    <strong class="stat-wsm-value">Aktif</strong>
                </div>
                <div class="stat-wsm-lime">
                    <span class="stat-wsm-label">Karir</span>
                    <strong class="stat-wsm-value">Terbuka</strong>
                </div>
            </div>
        </div>
    </section>

    <section class="border-y border-line bg-paper px-4 py-12 sm:px-6 sm:py-16">
        <div class="mx-auto max-w-6xl">
            <h2 class="text-[30px] font-black tracking-tight">Yang kami kerjakan</h2>
            <p class="mt-1 max-w-xl text-[15px] text-muted">
                Ringkasan singkat layanan/keunggulan tim — detail lengkap ada di halaman Layanan.
            </p>
            <div class="mt-8 grid gap-3.5 sm:grid-cols-3">
                <div class="card-wsm-white">
                    <h3 class="text-lg font-black">Produksi Musik</h3>
                    <p class="mt-2 text-sm text-muted">Dari proses kreatif sampai rilis.</p>
                </div>
                <div class="card-wsm-white">
                    <h3 class="text-lg font-black">Kampanye & Promosi</h3>
                    <p class="mt-2 text-sm text-muted">Menghubungkan karya ke pendengar.</p>
                </div>
                <div class="card-wsm-white">
                    <h3 class="text-lg font-black">Operasional Tim</h3>
                    <p class="mt-2 text-sm text-muted">Mengelola tim di balik layar.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-12 sm:px-6 sm:py-16">
        <div class="rounded-wsm-lg bg-ink px-6 py-10 text-center text-white sm:rounded-wsm-xl sm:px-8 sm:py-12 lg:px-16">
            <h2 class="text-[26px] font-black tracking-tight sm:text-[32px] lg:text-[40px]">Mau gabung dengan tim kami?</h2>
            <p class="mx-auto mt-3 max-w-md text-sm text-white/70">
                Lihat posisi yang sedang kami buka dan jadi bagian dari WSM.
            </p>
            <a href="{{ route('public.careers') }}" class="btn-wsm-blue mt-7 inline-flex">Lihat Lowongan</a>
        </div>
    </section>
@endsection
