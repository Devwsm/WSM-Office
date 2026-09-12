@extends('layouts.app', ['title' => 'Edit Kontrak', 'navActive' => 'modules'])

@section('content')
    <div class="mb-6">
        <a href="{{ route('dashboard.contracts.index') }}" class="text-[11px] font-extrabold text-muted">←
            Contract Monitoring</a>
        <h2 class="mt-2 text-[36px] font-black leading-[0.98] tracking-tight">Edit Kontrak</h2>
    </div>

    <form method="POST" action="{{ route('dashboard.contracts.update', $contract) }}" enctype="multipart/form-data"
        class="card-wsm-white">
        @csrf
        @method('PATCH')
        @include('dashboard.contracts._form', ['contract' => $contract, 'employees' => $employees])

        <div class="mt-6 flex gap-3">
            <button type="submit" class="btn-wsm-black">Simpan Perubahan</button>
            <a href="{{ route('dashboard.contracts.index') }}" class="btn-wsm-white">Batal</a>
        </div>
    </form>
@endsection
