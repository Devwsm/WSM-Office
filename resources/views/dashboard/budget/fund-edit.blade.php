{{--
    dashboard/budget/fund-edit.blade.php
    ---------------------------------------------------------------------
    "Project Budget" = dana KESELURUHAN untuk semua project: satu angka,
    diinput manual, hanya diedit (tidak ada tambah/hapus), dan bisa diisi
    walau belum ada project. Halaman sendiri, BUKAN modal, karena data
    anggaran sensitif.

    Pembandingnya "Budget Allocation" (jumlah budget semua item). Selisihnya
    dihitung di halaman utama (Unallocated / Over-allocated).
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Project Budget', 'navActive' => 'modules'])

@php
    $rp = fn($value) => \App\Models\PayrollRecord::formatRupiah($value);
@endphp

@section('content')
    <div class="mb-6">
        <a href="{{ route('dashboard.budget.index') }}" class="text-[11px] font-extrabold text-muted">← Project
            Budgeting</a>
        <h2 class="mt-2 text-3xl font-black leading-[0.98] tracking-tight sm:text-[36px]">Project Budget</h2>
        <p class="mt-1 text-[13px] text-muted">Dana keseluruhan untuk semua project.</p>
    </div>

    <form method="POST" action="{{ route('dashboard.budget.fund.update') }}" class="card-wsm-white max-w-xl">
        @csrf
        @method('PATCH')

        <div class="grid gap-4">
            <div>
                <label class="field-label-wsm mb-1.5" for="projectBudget">Project Budget (Rp)</label>
                <input type="number" id="projectBudget" step="1000" min="0" name="project_budget"
                    value="{{ old('project_budget', $fund !== null ? rtrim(rtrim(number_format($fund, 2, '.', ''), '0'), '.') : '') }}"
                    class="input-wsm" required autofocus>
                @error('project_budget')
                    <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
                @enderror
                <p class="mt-1.5 text-[11px] text-muted">Total dana yang tersedia untuk seluruh project. Angka ini
                    tidak berubah otomatis saat budget item ditambah atau diubah.</p>
            </div>

            <div class="rounded-2xl bg-[#f6f2eb] p-3.5 text-[12px]">
                <p class="text-[10px] font-black uppercase tracking-wide text-muted">Budget Allocation saat ini</p>
                <p class="mt-1 text-lg font-black">{{ $rp($allocation) }}</p>
                <p class="mt-0.5 text-[11px] text-muted">Jumlah budget semua item di semua project.</p>
            </div>
        </div>

        <div class="mt-6 flex gap-3">
            <button type="submit" class="btn-wsm-black">Simpan</button>
            <a href="{{ route('dashboard.budget.index') }}" class="btn-wsm-white">Batal</a>
        </div>
    </form>
@endsection
