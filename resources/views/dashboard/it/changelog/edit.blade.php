@extends('layouts.app', ['title' => 'Edit Changelog', 'navActive' => 'modules'])

@section('content')
    <div class="mb-6">
        <a href="{{ route('dashboard.it.changelog.index') }}" class="text-[11px] font-extrabold text-muted">←
            System Changelog</a>
        <h2 class="mt-2 text-[36px] font-black leading-[0.98] tracking-tight">Edit Changelog</h2>
    </div>

    <form method="POST" action="{{ route('dashboard.it.changelog.update', $changelog) }}" class="card-wsm-white">
        @csrf
        @method('PATCH')
        @include('dashboard.it.changelog._form', ['changelog' => $changelog])

        <div class="mt-6 flex gap-3">
            <button type="submit" class="btn-wsm-black">Simpan Perubahan</button>
            <a href="{{ route('dashboard.it.changelog.index') }}" class="btn-wsm-white">Batal</a>
        </div>
    </form>
@endsection
