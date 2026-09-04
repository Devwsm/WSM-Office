{{--
    public/services.blade.php — Layanan/Portofolio
    ---------------------------------------------------------------------
    Placeholder daftar layanan generik. Kalau perusahaan mau pamer hasil
    kerja/klien (portofolio), tambahkan section terpisah di bawah —
    butuh materi (gambar/nama klien) dari tim dulu.
    ---------------------------------------------------------------------
--}}
@extends('layouts.public', ['title' => 'Layanan'])

@section('content')
    <section class="mx-auto max-w-4xl px-4 pb-10 pt-10 sm:px-6 sm:pb-14 sm:pt-14 lg:pt-20">
        <span class="badge-wsm-blue">Layanan</span>
        <h1 class="mt-5 text-[30px] font-black leading-[1.02] tracking-tight sm:text-[40px] lg:text-[52px]">
            Apa yang kami kerjakan.
        </h1>
        <p class="mt-5 max-w-2xl text-[16px] text-muted">
            Ringkasan layanan/keunggulan tim. Ganti deskripsi di bawah dengan detail resmi begitu
            materinya siap dari tim.
        </p>
    </section>

    <section class="border-y border-line bg-paper px-4 py-10 sm:px-6 sm:py-14">
        <div class="mx-auto grid max-w-4xl gap-3.5 sm:grid-cols-2">
            <div class="card-wsm-white">
                <span class="badge-wsm-blue">Produksi</span>
                <h2 class="mt-3 text-xl font-black">Produksi Musik</h2>
                <p class="mt-2 text-sm text-muted">
                    Placeholder deskripsi layanan produksi musik.
                </p>
            </div>
            <div class="card-wsm-white">
                <span class="badge-wsm-yellow">Kampanye</span>
                <h2 class="mt-3 text-xl font-black">Kampanye & Promosi</h2>
                <p class="mt-2 text-sm text-muted">
                    Placeholder deskripsi layanan kampanye & promosi.
                </p>
            </div>
            <div class="card-wsm-white">
                <span class="badge-wsm-green">Kreatif</span>
                <h2 class="mt-3 text-xl font-black">Arahan Kreatif</h2>
                <p class="mt-2 text-sm text-muted">
                    Placeholder deskripsi layanan arahan kreatif.
                </p>
            </div>
            <div class="card-wsm-white">
                <span class="badge-wsm-gray">Operasional</span>
                <h2 class="mt-3 text-xl font-black">Manajemen Tim</h2>
                <p class="mt-2 text-sm text-muted">
                    Placeholder deskripsi layanan manajemen operasional tim.
                </p>
            </div>
        </div>
    </section>

    {{-- Portofolio (opsional) — TODO: tambahkan begitu ada materi klien/hasil kerja --}}
@endsection
