{{--
    public/home.blade.php — Beranda
    ---------------------------------------------------------------------
    Judul, tagline, dan 4 kartu di bagian atas dikelola Owner lewat
    Pengaturan Kantor → Beranda Publik (OfficeSetting::landing()).

    2026-10-10 — bagian bawahnya diisi profil artis Whisnu Santika
    (statistik, album Map of Feelings, sorotan karier, rilisan pilihan).
    Semua teks/angka/tautan-nya ada di config/public_site.php, bukan di
    file ini, jadi memperbarui isi tidak perlu menyentuh Blade.

    Animasi (ease-in-out) dipasang lewat atribut:
      data-reveal            muncul memudar naik saat masuk layar
      data-reveal-delay="ms" jeda antar elemen bersaudara
      data-countup           angka menghitung naik (lihat js/public-motion.js)
      .wsm-rise / .wsm-float animasi CSS murni saat halaman dibuka
    Semuanya mati otomatis kalau perangkat memilih "kurangi gerakan",
    dan tanpa JavaScript konten tetap tampil penuh.
    ---------------------------------------------------------------------
--}}
@extends('layouts.public', ['title' => 'Beranda'])

@php
    $site = config('public_site');
    $artist = $site['artist'];
    $album = $site['album'];
@endphp

@section('content')
    {{-- ===== Hero ===== --}}
    <section class="mx-auto max-w-6xl px-4 pb-10 pt-10 sm:px-6 sm:pb-16 sm:pt-14 lg:pt-20">
        <div class="grid gap-10 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
            <div>
                <span class="badge-wsm-blue wsm-rise" style="--d: 0ms">Whisnu Santika Musik</span>
                <h1 class="wsm-rise mt-5 text-[32px] font-black leading-[1.02] tracking-tight sm:text-[44px] lg:text-[64px]"
                    style="--d: 120ms">
                    {{ $landing['headline'] }}
                </h1>
                <p class="wsm-rise mt-5 max-w-lg text-[17px] text-muted" style="--d: 240ms">
                    {{ $landing['tagline'] }}
                </p>
                <div class="wsm-rise mt-8 flex flex-wrap gap-3" style="--d: 360ms">
                    <a href="{{ $album['url'] }}" target="_blank" rel="noopener noreferrer" class="btn-wsm-black">
                        Dengarkan {{ $album['title'] }}
                    </a>
                    <a href="{{ route('public.about') }}" class="btn-wsm-white">Tentang Kami</a>
                </div>
            </div>

            {{-- 4 banner: label, judul, dan warna dikelola Owner. Warna & teks
                dipasang inline supaya bebas dari palet kelas bawaan. --}}
            <div class="grid grid-cols-2 gap-3.5">
                @foreach ($landing['cards'] as $card)
                    <div class="wsm-rise" style="--d: {{ 200 + $loop->index * 120 }}ms">
                        <div class="wsm-float flex min-h-37.5 flex-col justify-between rounded-wsm-lg p-5"
                            style="--f: {{ $loop->index * 350 }}ms; background-color: {{ $card['color'] }}; color: {{ $card['text'] }}">
                            <span class="stat-wsm-label">{{ $card['label'] }}</span>
                            <strong class="stat-wsm-value wrap-break-word">{{ $card['title'] }}</strong>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===== Profil artis + statistik ===== --}}
    <section class="px-4 sm:px-6">
        <div class="mx-auto max-w-6xl rounded-wsm-lg bg-ink px-6 py-10 text-white sm:rounded-wsm-xl sm:px-10 sm:py-14 lg:px-16"
            data-reveal>
            <div class="grid gap-10 lg:grid-cols-[1.2fr_0.8fr] lg:items-center">
                <div>
                    <span class="text-[11px] font-black uppercase tracking-widest text-brand-lime">
                        {{ $artist['role'] }}
                    </span>
                    <h2 class="mt-3 text-[30px] font-black leading-tight tracking-tight sm:text-[40px]">
                        {{ $artist['name'] }}
                        <span class="block text-white/60">{{ $artist['title'] }}</span>
                    </h2>
                    <p class="mt-5 max-w-xl text-[15px] leading-relaxed text-white/70">{{ $artist['summary'] }}</p>

                    <div class="mt-6 flex flex-wrap gap-2">
                        @foreach ($artist['genres'] as $genre)
                            <span class="wsm-chip-dark" data-reveal data-reveal-delay="{{ $loop->index * 80 }}">
                                {{ $genre }}
                            </span>
                        @endforeach
                    </div>

                    <a href="{{ $artist['official_site'] }}" target="_blank" rel="noopener noreferrer"
                        class="wsm-link-arrow mt-8 inline-flex items-center gap-2 text-sm font-extrabold text-white">
                        Profil resmi di whisnusantika.com <span aria-hidden="true">→</span>
                    </a>
                </div>

                <div>
                    <div class="grid gap-3">
                        @foreach ($site['stats'] as $stat)
                            <div class="rounded-wsm bg-white/8 px-5 py-4" data-reveal
                                data-reveal-delay="{{ $loop->index * 140 }}">
                                <div class="text-[34px] font-black leading-none tracking-tight">
                                    <span data-countup="{{ $stat['value'] }}"
                                        data-decimals="{{ $stat['decimals'] }}">{{ number_format($stat['value'], $stat['decimals'], '.', '') }}</span>{{ $stat['suffix'] }}
                                </div>
                                <div class="mt-1 text-xs font-extrabold uppercase tracking-wide text-white/55">
                                    {{ $stat['label'] }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <p class="mt-3 text-[11px] text-white/40">{{ $site['stats_note'] }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== Album terbaru ===== --}}
    <section class="mx-auto max-w-6xl px-4 py-12 sm:px-6 sm:py-16">
        <div class="wsm-album grid gap-8 rounded-wsm-lg bg-brand-blue p-6 text-white sm:rounded-wsm-xl sm:p-10 lg:grid-cols-[1fr_1fr] lg:items-center"
            data-reveal>
            <div>
                <span
                    class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-[10px] font-black uppercase tracking-widest">
                    {{ $album['badge'] }}
                </span>
                <h2 class="mt-4 text-[34px] font-black leading-none tracking-tight sm:text-[48px]">{{ $album['title'] }}
                </h2>
                <p class="mt-4 max-w-md text-[15px] leading-relaxed text-white/80">{{ $album['text'] }}</p>
                <a href="{{ $album['url'] }}" target="_blank" rel="noopener noreferrer"
                    class="mt-7 inline-flex items-center justify-center rounded-full bg-white px-5 py-3 text-sm font-extrabold text-brand-blue transition-transform duration-500 ease-in-out hover:-translate-y-0.5">
                    Buka Map of Feelings
                </a>
            </div>
            <div>
                <p class="text-[11px] font-black uppercase tracking-widest text-white/60">Kolaborasi vokal</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($album['collaborators'] as $name)
                        <span class="wsm-chip-light" data-reveal
                            data-reveal-delay="{{ $loop->index * 70 }}">{{ $name }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ===== Sorotan karier ===== --}}
    <section class="mx-auto max-w-6xl px-4 pb-12 sm:px-6 sm:pb-16">
        <div data-reveal>
            <h2 class="text-[30px] font-black tracking-tight">Sorotan perjalanan</h2>
            <p class="mt-1 max-w-xl text-[15px] text-muted">
                Aktif sebagai DJ sejak 2012 dan produser sejak 2017, kini tampil di panggung dalam dan luar negeri.
            </p>
        </div>
        <div class="mt-8 grid gap-3.5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($site['highlights'] as $item)
                <div data-reveal data-reveal-delay="{{ $loop->index * 120 }}">
                    <div class="wsm-lift flex h-full min-h-52 flex-col justify-between rounded-wsm-lg p-5"
                        style="background-color: {{ $item['color'] }}; color: {{ \App\Models\OfficeSetting::readableTextOn($item['color']) }}">
                        <span class="stat-wsm-label">{{ $item['label'] }}</span>
                        <div>
                            <strong
                                class="text-[22px] font-black leading-tight tracking-tight">{{ $item['title'] }}</strong>
                            <p class="mt-2 text-[13px] leading-relaxed opacity-85">{{ $item['text'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ===== Rilisan pilihan ===== --}}
    <section class="border-y border-line bg-paper px-4 py-12 sm:px-6 sm:py-16">
        <div class="mx-auto max-w-6xl">
            <div data-reveal>
                <h2 class="text-[30px] font-black tracking-tight">Rilisan pilihan</h2>
                <p class="mt-1 max-w-xl text-[15px] text-muted">
                    Beberapa karya yang bisa langsung didengarkan di platform musik favoritmu.
                </p>
            </div>
            <div class="mt-8 grid gap-3.5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($site['releases'] as $release)
                    @php
                        $color = $site['release_colors'][$loop->index % count($site['release_colors'])];
                        $textColor = \App\Models\OfficeSetting::readableTextOn($color);
                    @endphp
                    <a href="{{ $release['url'] }}" target="_blank" rel="noopener noreferrer"
                        class="wsm-lift group flex min-h-44 flex-col justify-between rounded-wsm-lg p-5"
                        style="background-color: {{ $color }}; color: {{ $textColor }}" data-reveal
                        data-reveal-delay="{{ ($loop->index % 4) * 100 }}">
                        <span class="stat-wsm-label">{{ $release['year'] ?? 'Single' }}</span>
                        <div>
                            <strong
                                class="block text-[20px] font-black leading-tight tracking-tight">{{ $release['title'] }}</strong>
                            <span class="mt-1 block text-[12px] opacity-80">{{ $release['artists'] }}</span>
                            <span class="mt-3 inline-flex items-center gap-1.5 text-[12px] font-extrabold">
                                Dengarkan
                                <span aria-hidden="true" class="wsm-link-arrow-glyph">→</span>
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-8 flex flex-wrap items-center gap-x-5 gap-y-2" data-reveal>
                <span class="text-xs font-extrabold text-muted">Ikuti Whisnu:</span>
                @foreach ($site['socials'] as $social)
                    <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer"
                        class="text-xs font-extrabold text-ink underline-offset-4 hover:underline">
                        {{ $social['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===== Yang kami kerjakan ===== --}}
    <section class="mx-auto max-w-6xl px-4 py-12 sm:px-6 sm:py-16">
        <div data-reveal>
            <h2 class="text-[30px] font-black tracking-tight">Yang kami kerjakan</h2>
            <p class="mt-1 max-w-xl text-[15px] text-muted">
                Tim di balik karya Whisnu Santika — detail lengkap ada di halaman Layanan.
            </p>
        </div>
        <div class="mt-8 grid gap-3.5 sm:grid-cols-3">
            @foreach ($site['work'] as $item)
                <div class="card-wsm-white wsm-lift" data-reveal data-reveal-delay="{{ $loop->index * 120 }}">
                    <h3 class="text-lg font-black">{{ $item['title'] }}</h3>
                    <p class="mt-2 text-sm text-muted">{{ $item['text'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ===== Ajakan karir ===== --}}
    <section class="mx-auto max-w-6xl px-4 pb-12 sm:px-6 sm:pb-16">
        <div class="rounded-wsm-lg bg-ink px-6 py-10 text-center text-white sm:rounded-wsm-xl sm:px-8 sm:py-12 lg:px-16"
            data-reveal>
            <h2 class="text-[26px] font-black tracking-tight sm:text-[32px] lg:text-[40px]">Mau gabung dengan tim kami?
            </h2>
            <p class="mx-auto mt-3 max-w-md text-sm text-white/70">
                Lihat posisi yang sedang kami buka dan jadi bagian dari WSM.
            </p>
            <a href="{{ route('public.careers') }}" class="btn-wsm-blue mt-7 inline-flex">Lihat Lowongan</a>
        </div>
    </section>
@endsection
