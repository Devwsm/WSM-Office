@extends('layouts.app', ['title' => 'Tambah Memo/MoM', 'navActive' => 'modules'])

@section('content')
    <div class="mb-6">
        <a href="{{ route('dashboard.work.index') }}" class="text-[11px] font-extrabold text-muted">← MoM &
            Memo</a>
        <h2 class="mt-2 text-[36px] font-black leading-[0.98] tracking-tight">Tambah Memo/MoM</h2>
    </div>

    <form method="POST" action="{{ route('dashboard.work.store') }}" class="card-wsm-white">
        @csrf
        @include('dashboard.work._form', ['memo' => null])

        <div class="mt-6 flex gap-3">
            <button type="submit" class="btn-wsm-black">Simpan</button>
            <a href="{{ route('dashboard.work.index') }}" class="btn-wsm-white">Batal</a>
        </div>
    </form>
@endsection
