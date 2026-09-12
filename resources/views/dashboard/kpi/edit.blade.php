@extends('layouts.app', ['title' => 'Edit KPI', 'navActive' => 'modules'])

@section('content')
    <div class="mb-6">
        <a href="{{ route('dashboard.kpi.index') }}" class="text-[11px] font-extrabold text-muted">← KPI &
            Performance</a>
        <h2 class="mt-2 text-[36px] font-black leading-[0.98] tracking-tight">Edit KPI</h2>
    </div>

    <form method="POST" action="{{ route('dashboard.kpi.update', $kpi) }}" class="card-wsm-white">
        @csrf
        @method('PATCH')
        @include('dashboard.kpi._form', ['kpi' => $kpi, 'employees' => $employees])

        <div class="mt-6 flex gap-3">
            <button type="submit" class="btn-wsm-black">Simpan Perubahan</button>
            <a href="{{ route('dashboard.kpi.index') }}" class="btn-wsm-white">Batal</a>
        </div>
    </form>
@endsection
