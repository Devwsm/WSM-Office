{{--
    dashboard/budget/project-edit.blade.php
    ---------------------------------------------------------------------
    "Project Budget" = anggaran awal 1 project. Hanya diedit (tidak ada
    tambah/hapus): satu angka per project. Halaman sendiri, BUKAN modal,
    karena data anggaran sensitif.

    Bedanya dengan "Budget Allocation": itu jumlah budget semua item.
    Selisih keduanya (belum dialokasikan) dihitung di halaman utama.
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Project Budget', 'navActive' => 'modules'])

@php
    $rp = fn($value) => \App\Models\PayrollRecord::formatRupiah($value);
    $allocation = (float) $project->budgets()->sum('budget');
@endphp

@section('content')
    <div class="mb-6">
        <a href="{{ route('dashboard.budget.index', ['project_id' => $project->id]) }}"
            class="text-[11px] font-extrabold text-muted">← Project Budgeting</a>
        <h2 class="mt-2 text-3xl font-black leading-[0.98] tracking-tight sm:text-[36px]">Project Budget</h2>
        <p class="mt-1 text-[13px] text-muted">{{ $project->name }}</p>
    </div>

    <form method="POST" action="{{ route('dashboard.budget.plan.update', $project) }}" class="card-wsm-white max-w-xl">
        @csrf
        @method('PATCH')

        <div class="grid gap-4">
            <div>
                <label class="field-label-wsm mb-1.5" for="projectBudget">Project Budget (Rp)</label>
                <input type="number" id="projectBudget" step="1000" min="0" name="project_budget"
                    value="{{ old('project_budget', $plan ? rtrim(rtrim(number_format($plan->project_budget, 2, '.', ''), '0'), '.') : '') }}"
                    class="input-wsm" required autofocus>
                @error('project_budget')
                    <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
                @enderror
                <p class="mt-1.5 text-[11px] text-muted">Anggaran awal untuk seluruh project. Angka ini tidak otomatis
                    berubah saat item ditambah atau diubah.</p>
            </div>

            <div class="rounded-2xl bg-[#f6f2eb] p-3.5 text-[12px]">
                <p class="text-[10px] font-black uppercase tracking-wide text-muted">Budget Allocation saat ini</p>
                <p class="mt-1 text-lg font-black">{{ $rp($allocation) }}</p>
                <p class="mt-0.5 text-[11px] text-muted">Jumlah budget semua item di project ini.</p>
            </div>
        </div>

        <div class="mt-6 flex gap-3">
            <button type="submit" class="btn-wsm-black">Simpan</button>
            <a href="{{ route('dashboard.budget.index', ['project_id' => $project->id]) }}" class="btn-wsm-white">Batal</a>
        </div>
    </form>
@endsection
