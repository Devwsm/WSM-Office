{{--
    public/careers.blade.php — Karir
    ---------------------------------------------------------------------
    Fase 3: data lowongan asli dari job_openings (status = published),
    lihat PageController@careers. Detail + form lamaran di
    public/career-show.blade.php.
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
            @if ($openings->isEmpty())
                <div class="card-wsm-white text-center">
                    <p class="text-lg font-black">Belum ada lowongan yang dibuka</p>
                    <p class="mx-auto mt-2 max-w-sm text-sm text-muted">
                        Cek lagi nanti, atau hubungi kami lewat halaman Kontak kalau kamu tertarik
                        gabung dengan tim WSM.
                    </p>
                    <a href="{{ route('public.contact') }}" class="btn-wsm-black mt-6 inline-flex">Hubungi Kami</a>
                </div>
            @else
                <div class="grid gap-3.5">
                    @foreach ($openings as $opening)
                        <a href="{{ route('public.careers.show', $opening) }}"
                            class="card-wsm-white flex flex-wrap items-center justify-between gap-3 hover:bg-white">
                            <div>
                                <p class="text-base font-black">{{ $opening->title }}</p>
                                <div class="mt-1.5 flex flex-wrap items-center gap-2">
                                    <span class="badge-wsm-blue">{{ $opening->employmentTypeLabel() }}</span>
                                    @if ($opening->division)
                                        <span class="badge-wsm-gray">{{ $opening->division }}</span>
                                    @endif
                                </div>
                            </div>
                            <span class="btn-wsm-white text-xs">Lihat Detail &amp; Lamar</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
