{{--
    recruitment/openings/edit.blade.php — Edit Lowongan
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Edit Lowongan', 'navActive' => 'openings'])

@section('content')
    <h1 class="text-[28px] font-black leading-[1.05] tracking-tight sm:text-[32px]">Edit Lowongan</h1>
    <p class="mt-1 text-sm text-muted">{{ $opening->title }}</p>

    <form method="POST" action="{{ route('recruitment.openings.update', $opening) }}" class="card-wsm-white mt-6">
        @csrf
        @method('PUT')
        @include('recruitment.openings._form', ['opening' => $opening])

        <div class="mt-6 flex flex-wrap gap-3">
            <button type="submit" class="btn-wsm-black">Simpan Perubahan</button>
            <a href="{{ route('recruitment.openings.index') }}" class="btn-wsm-white">Batal</a>
        </div>
    </form>
@endsection
