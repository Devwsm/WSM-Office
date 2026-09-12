@extends('layouts.app', ['title' => 'Tambah KPI', 'navActive' => 'modules'])

@section('content')
    <div class="mb-6">
        <a href="{{ route('dashboard.kpi.index') }}" class="text-[11px] font-extrabold text-muted">← KPI &
            Performance</a>
        <h2 class="mt-2 text-[36px] font-black leading-[0.98] tracking-tight">Tambah KPI</h2>
    </div>

    <form method="POST" action="{{ route('dashboard.kpi.store') }}" class="card-wsm-white">
        @csrf
        @include('dashboard.kpi._form', ['kpi' => null, 'employees' => $employees])

        <div class="mt-6 flex gap-3">
            <button type="submit" class="btn-wsm-black">Simpan</button>
            <a href="{{ route('dashboard.kpi.index') }}" class="btn-wsm-white">Batal</a>
        </div>
    </form>
@endsection
