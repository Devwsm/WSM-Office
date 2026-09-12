@extends('layouts.app', ['title' => 'Upload Kontrak', 'navActive' => 'modules'])

@section('content')
    <div class="mb-6">
        <a href="{{ route('dashboard.contracts.index') }}" class="text-[11px] font-extrabold text-muted">←
            Contract Monitoring</a>
        <h2 class="mt-2 text-[36px] font-black leading-[0.98] tracking-tight">Upload Kontrak</h2>
    </div>

    <form method="POST" action="{{ route('dashboard.contracts.store') }}" enctype="multipart/form-data" class="card-wsm-white">
        @csrf
        @include('dashboard.contracts._form', ['contract' => null, 'employees' => $employees])

        <div class="mt-6 flex gap-3">
            <button type="submit" class="btn-wsm-black">Simpan</button>
            <a href="{{ route('dashboard.contracts.index') }}" class="btn-wsm-white">Batal</a>
        </div>
    </form>
@endsection
