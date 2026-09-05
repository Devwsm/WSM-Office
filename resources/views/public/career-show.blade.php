{{--
    public/career-show.blade.php — Detail Lowongan & Form Lamaran
    ---------------------------------------------------------------------
    Fase 3. Form lamaran masih teks saja (nama, email, telepon, pesan) —
    upload CV menyusul nanti.
    ---------------------------------------------------------------------
--}}
@extends('layouts.public', ['title' => $opening->title])

@section('content')
    <section class="mx-auto max-w-4xl px-4 pb-10 pt-10 sm:px-6 sm:pb-14 sm:pt-14 lg:pt-20">
        <a href="{{ route('public.careers') }}" class="text-xs font-bold text-muted">&larr; Kembali ke Karir</a>

        <div class="mt-4 flex flex-wrap items-center gap-2">
            <span class="badge-wsm-blue">{{ $opening->employmentTypeLabel() }}</span>
            @if ($opening->division)
                <span class="badge-wsm-gray">{{ $opening->division }}</span>
            @endif
        </div>

        <h1 class="mt-5 text-[30px] font-black leading-[1.02] tracking-tight sm:text-[40px]">
            {{ $opening->title }}
        </h1>
    </section>

    <section class="border-t border-line bg-paper px-4 py-10 sm:px-6 sm:py-14">
        <div class="mx-auto grid max-w-4xl gap-8 lg:grid-cols-[1.2fr_0.8fr]">
            <div class="grid gap-6">
                <div class="card-wsm-white">
                    <p class="field-label-wsm">Deskripsi Pekerjaan</p>
                    <p class="mt-2 whitespace-pre-line text-sm text-[#3a362f]">{{ $opening->description }}</p>
                </div>

                @if ($opening->requirements)
                    <div class="card-wsm-white">
                        <p class="field-label-wsm">Kualifikasi / Syarat</p>
                        <p class="mt-2 whitespace-pre-line text-sm text-[#3a362f]">{{ $opening->requirements }}</p>
                    </div>
                @endif
            </div>

            <div class="card-wsm h-fit">
                <p class="mb-1 text-lg font-black">Lamar Posisi Ini</p>
                <p class="mb-5 text-xs text-muted">Isi data di bawah, tim kami akan menghubungi lewat email.</p>

                <form method="POST" action="{{ route('public.careers.apply', $opening) }}" class="grid gap-4">
                    @csrf
                    <div class="grid gap-1.5">
                        <label class="field-label-wsm">Nama Lengkap</label>
                        <input type="text" name="name" required value="{{ old('name') }}" class="input-wsm">
                    </div>
                    <div class="grid gap-1.5">
                        <label class="field-label-wsm">Email</label>
                        <input type="email" name="email" required value="{{ old('email') }}" class="input-wsm">
                    </div>
                    <div class="grid gap-1.5">
                        <label class="field-label-wsm">Telepon (opsional)</label>
                        <input type="text" name="phone" value="{{ old('phone') }}" class="input-wsm">
                    </div>
                    <div class="grid gap-1.5">
                        <label class="field-label-wsm">Pesan / Motivasi (opsional)</label>
                        <textarea name="message" rows="5" class="input-wsm">{{ old('message') }}</textarea>
                    </div>
                    <button type="submit" class="btn-wsm-black w-full">Kirim Lamaran</button>
                </form>
            </div>
        </div>
    </section>
@endsection
