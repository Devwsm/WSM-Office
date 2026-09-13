@extends('layouts.app', ['title' => 'Tambah Royalty Entry', 'navActive' => 'modules'])

@section('content')
    <div class="mb-6">
        <a href="{{ route('dashboard.royalty.index') }}" class="text-[11px] font-extrabold text-muted">← Royalty
            Dashboard</a>
        <h2 class="mt-2 text-[36px] font-black leading-[0.98] tracking-tight">Tambah Royalty Entry</h2>
    </div>

    <form method="POST" action="{{ route('dashboard.royalty.store') }}" class="card-wsm-white">
        @csrf
        @include('dashboard.royalty._form', ['royalty' => null])

        <div class="mt-6 flex gap-3">
            <button type="submit" class="btn-wsm-black">Simpan</button>
            <a href="{{ route('dashboard.royalty.index') }}" class="btn-wsm-white">Batal</a>
        </div>
    </form>
@endsection
