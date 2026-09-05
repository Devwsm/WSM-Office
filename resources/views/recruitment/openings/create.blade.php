{{--
    recruitment/openings/create.blade.php — Tambah Lowongan
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Tambah Lowongan', 'navActive' => 'openings'])

@section('content')
    <h1 class="text-[28px] font-black leading-[1.05] tracking-tight sm:text-[32px]">Tambah Lowongan</h1>
    <p class="mt-1 text-sm text-muted">Isi detail posisi yang mau dibuka.</p>

    <form method="POST" action="{{ route('recruitment.openings.store') }}" class="card-wsm-white mt-6">
        @csrf
        @include('recruitment.openings._form')

        <div class="mt-6 flex flex-wrap gap-3">
            <button type="submit" class="btn-wsm-black">Simpan Lowongan</button>
            <a href="{{ route('recruitment.openings.index') }}" class="btn-wsm-white">Batal</a>
        </div>
    </form>
@endsection
