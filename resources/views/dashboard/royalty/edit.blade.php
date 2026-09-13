@extends('layouts.app', ['title' => 'Edit Royalty Entry', 'navActive' => 'modules'])

@section('content')
    <div class="mb-6">
        <a href="{{ route('dashboard.royalty.index') }}" class="text-[11px] font-extrabold text-muted">← Royalty
            Dashboard</a>
        <h2 class="mt-2 text-[36px] font-black leading-[0.98] tracking-tight">Edit Royalty Entry</h2>
    </div>

    <form method="POST" action="{{ route('dashboard.royalty.update', $royalty) }}" class="card-wsm-white">
        @csrf
        @method('PATCH')
        @include('dashboard.royalty._form', ['royalty' => $royalty])

        <div class="mt-6 flex gap-3">
            <button type="submit" class="btn-wsm-black">Simpan Perubahan</button>
            <a href="{{ route('dashboard.royalty.index') }}" class="btn-wsm-white">Batal</a>
        </div>
    </form>
@endsection
