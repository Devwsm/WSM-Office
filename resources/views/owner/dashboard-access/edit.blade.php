{{--
    owner/dashboard-access/edit.blade.php
    ---------------------------------------------------------------------
    Fase 6a — cuma diakses lewat route role:owner. 7 dropdown (None/
    View/Manage), 1 per modul. 'None' di dropdown = hapus barisnya di
    DB (lihat DashboardAccessController::update()), bukan disimpan
    literal.
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Dashboard Access — ' . $employee->name, 'navActive' => 'employees'])

@section('content')
    <div class="mb-6">
        <h2 class="text-[36px] font-black leading-[0.98] tracking-tight">Dashboard Access</h2>
        <p class="mt-1 text-[13px] text-muted">
            {{ $employee->name }} — {{ $employee->roleLabel() }}
            @if ($employee->division)
                · {{ $employee->division }}
            @endif
        </p>
    </div>

    <form method="POST" action="{{ route('owner.employees.access.update', $employee) }}" class="card-wsm-white">
        @csrf
        @method('PATCH')

        <div class="mb-4 rounded-2xl bg-[#f2f0eb] p-3.5 text-[11px] text-[#5e5952]">
            <strong>View</strong> = cuma bisa lihat. <strong>Manage</strong> = bisa input/ubah data modul itu.
            Persetujuan izin/cuti & rekap absensi tetap ngikutin role (Manajer/HRD/Owner), bukan tabel ini.
        </div>

        <div class="grid gap-3">
            @foreach ($modules as $key => $meta)
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-wsm border border-line p-3.5">
                    <div class="min-w-0">
                        <strong class="block text-sm">{{ $meta['label'] }}</strong>
                        <span class="text-[11px] text-muted">{{ $meta['desc'] }}</span>
                    </div>
                    <select name="access[{{ $key }}]" class="input-wsm w-auto min-w-35">
                        <option value="" @selected(!isset($current[$key]))>None</option>
                        <option value="view" @selected(($current[$key]->level ?? null) === 'view')>View</option>
                        <option value="manage" @selected(($current[$key]->level ?? null) === 'manage')>Manage</option>
                    </select>
                </div>
            @endforeach
        </div>

        <div class="mt-6 flex gap-3">
            <button type="submit" class="btn-wsm-black">Simpan Akses</button>
            <a href="{{ route('owner.employees.index') }}" class="btn-wsm-white">Batal</a>
        </div>
    </form>
@endsection
