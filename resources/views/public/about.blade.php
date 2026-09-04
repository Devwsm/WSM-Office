{{--
    public/about.blade.php — Tentang Kami
    ---------------------------------------------------------------------
    Placeholder: visi misi & sejarah masih teks generik, tim opsional
    belum ditampilkan (butuh foto/data asli dari perusahaan dulu).
    ---------------------------------------------------------------------
--}}
@extends('layouts.public', ['title' => 'Tentang Kami'])

@section('content')
    <section class="mx-auto max-w-4xl px-4 pb-10 pt-10 sm:px-6 sm:pb-14 sm:pt-14 lg:pt-20">
        <span class="badge-wsm-blue">Tentang Kami</span>
        <h1 class="mt-5 text-[30px] font-black leading-[1.02] tracking-tight sm:text-[40px] lg:text-[52px]">
            Cerita di balik Whisnu Santika Music.
        </h1>
        <p class="mt-5 max-w-2xl text-[16px] text-muted">
            Teks pengantar tentang perusahaan — siapa kami, apa yang kami percaya, dan kenapa kami
            melakukan apa yang kami lakukan. Ganti dengan copy resmi dari tim begitu materinya siap.
        </p>
    </section>

    <section class="border-y border-line bg-paper px-4 py-10 sm:px-6 sm:py-14">
        <div class="mx-auto grid max-w-4xl gap-3.5 sm:grid-cols-2">
            <div class="card-wsm-white">
                <h2 class="text-xl font-black">Visi</h2>
                <p class="mt-2 text-sm text-muted">
                    Placeholder pernyataan visi perusahaan — isi dengan naskah resmi.
                </p>
            </div>
            <div class="card-wsm-white">
                <h2 class="text-xl font-black">Misi</h2>
                <p class="mt-2 text-sm text-muted">
                    Placeholder pernyataan misi perusahaan — isi dengan naskah resmi.
                </p>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-4xl px-4 py-10 sm:px-6 sm:py-14">
        <h2 class="text-[28px] font-black tracking-tight">Perjalanan Kami</h2>
        <p class="mt-2 max-w-xl text-sm text-muted">
            Placeholder timeline sejarah singkat — isi dengan poin-poin penting perjalanan
            perusahaan begitu datanya tersedia.
        </p>
        <div class="mt-7 grid gap-3">
            <div class="card-wsm-white flex items-center justify-between gap-4">
                <span class="text-sm font-black">Tahun —</span>
                <span class="text-sm text-muted">Tonggak sejarah placeholder</span>
            </div>
            <div class="card-wsm-white flex items-center justify-between gap-4">
                <span class="text-sm font-black">Tahun —</span>
                <span class="text-sm text-muted">Tonggak sejarah placeholder</span>
            </div>
        </div>
    </section>

    {{-- Tim (opsional) — TODO: aktifkan begitu ada foto & data tim resmi --}}
@endsection
