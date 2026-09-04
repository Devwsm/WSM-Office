{{--
    public/careers.blade.php — Karir
    ---------------------------------------------------------------------
    Fase 1: halaman & route-nya sudah ada, tapi datanya masih kosong
    ($openings dari PageController@careers). Detail lowongan + form
    lamaran publik baru dibangun di Fase 3 (Rekrutmen) begitu tabel
    `job_openings` & `applicants` ada.
    ---------------------------------------------------------------------
--}}
@extends('layouts.public', ['title' => 'Karir'])

@section('content')
    <section class="mx-auto max-w-4xl px-4 pb-10 pt-10 sm:px-6 sm:pb-14 sm:pt-14 lg:pt-20">
        <span class="badge-wsm-blue">Karir</span>
        <h1 class="mt-5 text-[30px] font-black leading-[1.02] tracking-tight sm:text-[40px] lg:text-[52px]">
            Gabung dengan tim WSM.
        </h1>
        <p class="mt-5 max-w-2xl text-[16px] text-muted">
            Posisi yang sedang kami buka akan muncul di sini.
        </p>
    </section>

    <section class="border-t border-line bg-paper px-4 py-10 sm:px-6 sm:py-14">
        <div class="mx-auto max-w-4xl">
            @if (count($openings) === 0)
                <div class="card-wsm-white text-center">
                    <p class="text-lg font-black">Belum ada lowongan yang dibuka</p>
                    <p class="mx-auto mt-2 max-w-sm text-sm text-muted">
                        Cek lagi nanti, atau hubungi kami lewat halaman Kontak kalau kamu tertarik
                        gabung dengan tim WSM.
                    </p>
                    <a href="{{ route('public.contact') }}" class="btn-wsm-black mt-6 inline-flex">Hubungi Kami</a>
                </div>
            @else
                {{-- TODO Fase 3: list lowongan (@foreach $openings) + link ke halaman detail --}}
            @endif
        </div>
    </section>
@endsection
