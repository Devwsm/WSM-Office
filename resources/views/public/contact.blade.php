{{--
    public/contact.blade.php — Kontak
    ---------------------------------------------------------------------
    Form kirim ke PageController@storeContact — validasi jalan, tapi
    pesan BELUM disimpan/dikirim ke mana pun (lihat TODO di controller).
    Alamat & telepon masih placeholder.
    ---------------------------------------------------------------------
--}}
@extends('layouts.public', ['title' => 'Kontak'])

@section('content')
    <section class="mx-auto max-w-4xl px-4 pb-12 pt-10 sm:px-6 sm:pb-16 sm:pt-14 lg:pt-20">
        <span class="badge-wsm-blue">Kontak</span>
        <h1 class="mt-5 text-[30px] font-black leading-[1.02] tracking-tight sm:text-[40px] lg:text-[52px]">
            Ada pertanyaan? Hubungi kami.
        </h1>

        <div class="mt-10 grid gap-8 lg:grid-cols-[0.8fr_1.2fr]">
            <div class="grid gap-3.5">
                <div class="card-wsm-white">
                    <p class="field-label-wsm">Alamat</p>
                    <p class="mt-1.5 text-sm text-muted">Placeholder alamat kantor WSM.</p>
                </div>
                <div class="card-wsm-white">
                    <p class="field-label-wsm">Telepon</p>
                    <p class="mt-1.5 text-sm text-muted">Placeholder nomor telepon.</p>
                </div>
                <div class="card-wsm-white">
                    <p class="field-label-wsm">Email</p>
                    <p class="mt-1.5 text-sm text-muted">Placeholder alamat email.</p>
                </div>
            </div>

            <div class="card-wsm">
                <form method="POST" action="{{ route('public.contact.store') }}" class="grid gap-4">
                    @csrf
                    <div class="grid gap-1.5">
                        <label class="field-label-wsm">Nama</label>
                        <input type="text" name="name" required value="{{ old('name') }}" class="input-wsm">
                    </div>
                    <div class="grid gap-1.5">
                        <label class="field-label-wsm">Email</label>
                        <input type="email" name="email" required value="{{ old('email') }}" class="input-wsm">
                    </div>
                    <div class="grid gap-1.5">
                        <label class="field-label-wsm">Pesan</label>
                        <textarea name="message" required rows="5" class="input-wsm">{{ old('message') }}</textarea>
                    </div>
                    <button type="submit" class="btn-wsm-black w-full">Kirim Pesan</button>
                </form>
            </div>
        </div>
    </section>
@endsection
