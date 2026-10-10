{{--
    public/services.blade.php — Layanan/Portofolio
    ---------------------------------------------------------------------
    Isi (pengantar + 4 layanan) ada di config/public_site.php bagian
    `services`. Kalau perusahaan mau pamer hasil kerja/klien (portofolio),
    tambahkan section terpisah di bawah — butuh materi dari tim dulu.
    ---------------------------------------------------------------------
--}}
@extends('layouts.public', ['title' => 'Layanan'])

@php($services = config('public_site.services'))

@section('content')
    <section class="mx-auto max-w-4xl px-4 pb-10 pt-10 sm:px-6 sm:pb-14 sm:pt-14 lg:pt-20">
        <span class="badge-wsm-blue wsm-rise" style="--d: 0ms">Layanan</span>
        <h1 class="wsm-rise mt-5 text-[30px] font-black leading-[1.02] tracking-tight sm:text-[40px] lg:text-[52px]"
            style="--d: 120ms">
            Apa yang kami kerjakan.
        </h1>
        <p class="wsm-rise mt-5 max-w-2xl text-[16px] text-muted" style="--d: 240ms">
            {{ $services['intro'] }}
        </p>
    </section>

    <section class="border-y border-line bg-paper px-4 py-10 sm:px-6 sm:py-14">
        <div class="mx-auto grid max-w-4xl gap-3.5 sm:grid-cols-2">
            @foreach ($services['items'] as $item)
                <div class="card-wsm-white wsm-lift" data-reveal data-reveal-delay="{{ ($loop->index % 2) * 120 }}">
                    <span class="badge-wsm-{{ $item['badge'] }}">{{ $item['tag'] }}</span>
                    <h2 class="mt-3 text-xl font-black">{{ $item['title'] }}</h2>
                    <p class="mt-2 text-sm text-muted">{{ $item['text'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Portofolio (opsional) — TODO: tambahkan begitu ada materi klien/hasil kerja --}}
@endsection
