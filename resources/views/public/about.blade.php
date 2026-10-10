{{--
    public/about.blade.php — Tentang Kami
    ---------------------------------------------------------------------
    Isi (pengantar, visi, misi, perjalanan) ada di config/public_site.php
    bagian `about`. Visi & misi adalah DRAF dari pernyataan publik Whisnu;
    konfirmasi atau ganti dengan naskah resmi tim. Tim opsional belum
    ditampilkan (butuh foto/data resmi).
    ---------------------------------------------------------------------
--}}
@extends('layouts.public', ['title' => 'Tentang Kami'])

@php($about = config('public_site.about'))

@section('content')
    <section class="mx-auto max-w-4xl px-4 pb-10 pt-10 sm:px-6 sm:pb-14 sm:pt-14 lg:pt-20">
        <span class="badge-wsm-blue wsm-rise" style="--d: 0ms">Tentang Kami</span>
        <h1 class="wsm-rise mt-5 text-[30px] font-black leading-[1.02] tracking-tight sm:text-[40px] lg:text-[52px]"
            style="--d: 120ms">
            Cerita di balik Whisnu Santika Musik.
        </h1>
        <p class="wsm-rise mt-5 max-w-2xl text-[16px] text-muted" style="--d: 240ms">
            {{ $about['intro'] }}
        </p>
    </section>

    <section class="border-y border-line bg-paper px-4 py-10 sm:px-6 sm:py-14">
        <div class="mx-auto grid max-w-4xl gap-3.5 sm:grid-cols-2">
            <div class="card-wsm-white" data-reveal>
                <h2 class="text-xl font-black">Visi</h2>
                <p class="mt-2 text-sm text-muted">{{ $about['vision'] }}</p>
            </div>
            <div class="card-wsm-white" data-reveal data-reveal-delay="120">
                <h2 class="text-xl font-black">Misi</h2>
                <ul class="mt-2 grid gap-2 text-sm text-muted">
                    @foreach ($about['mission'] as $item)
                        <li class="flex gap-2">
                            <span aria-hidden="true" class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-brand-blue"></span>
                            <span>{{ $item }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-4xl px-4 py-10 sm:px-6 sm:py-14">
        <div data-reveal>
            <h2 class="text-[28px] font-black tracking-tight">Perjalanan Kami</h2>
            <p class="mt-2 max-w-xl text-sm text-muted">{{ $about['timeline_intro'] }}</p>
        </div>
        <div class="mt-7 grid gap-3">
            @foreach ($about['timeline'] as $row)
                <div class="card-wsm-white flex items-center justify-between gap-4" data-reveal
                    data-reveal-delay="{{ ($loop->index % 3) * 100 }}">
                    <span class="text-sm font-black">{{ $row['year'] }}</span>
                    <span class="text-right text-sm text-muted">{{ $row['text'] }}</span>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Tim (opsional) — TODO: aktifkan begitu ada foto & data tim resmi --}}
@endsection
