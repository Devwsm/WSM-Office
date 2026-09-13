{{--
    dashboard/budget/index.blade.php
    ---------------------------------------------------------------------
    Fase 13 — listing ProjectBudget dikelompokkan per project
    ($entriesByProject, key = project_id). Tiap grup nunjukin subtotal
    Budget/Actual/Variance sendiri — variance negatif (over budget)
    dikasih warna merah lewat ProjectBudget::variance().
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Project Budgeting', 'navActive' => 'modules'])

@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3.5">
        <div>
            <a href="{{ route('dashboard.index') }}" class="text-[11px] font-extrabold text-muted">← Dashboard</a>
            <h2 class="mt-2 text-[36px] font-black leading-[0.98] tracking-tight">Project Budgeting</h2>
            <p class="mt-1 text-[13px] text-muted">Budget vs actual per project.</p>
        </div>
        @if (auth()->user()->canManageModule('budget'))
            <a href="{{ route('dashboard.budget.create') }}" class="btn-wsm-black">+ Tambah Budget</a>
        @endif
    </div>

    <form method="GET" class="mb-4 flex flex-wrap items-center gap-2">
        <select name="project_id" onchange="this.form.submit()"
            class="rounded-2xl border border-line bg-white px-3.5 py-2 text-[11px] font-bold text-ink">
            <option value="">Semua Project</option>
            @foreach ($projects as $project)
                <option value="{{ $project->id }}" @selected($selectedProjectId === $project->id)>
                    {{ $project->name }}
                </option>
            @endforeach
        </select>
    </form>

    @if ($entriesByProject->isEmpty())
        <div class="card-wsm-white text-center">
            <p class="text-xs text-muted">Belum ada baris budget.</p>
        </div>
    @else
        <div class="grid gap-5">
            @foreach ($entriesByProject as $projectId => $rows)
                @php
                    $sumBudget = $rows->sum('budget');
                    $sumActual = $rows->sum('actual');
                    $variance = $sumBudget - $sumActual;
                @endphp
                <div class="rounded-wsm border border-line bg-white p-4.5">
                    <div class="mb-3.5 flex flex-wrap items-center justify-between gap-2 border-b border-[#eee8df] pb-3.5">
                        <strong class="text-sm">{{ $rows->first()->project->name }}</strong>
                        <div class="flex flex-wrap gap-3 text-[11px]">
                            <span>Budget: <strong>{{ \App\Models\PayrollRecord::formatRupiah($sumBudget) }}</strong></span>
                            <span>Actual: <strong>{{ \App\Models\PayrollRecord::formatRupiah($sumActual) }}</strong></span>
                            <span class="{{ $variance < 0 ? 'text-[#a83d35]' : 'text-[#3d7a4d]' }}">
                                Variance: <strong>{{ \App\Models\PayrollRecord::formatRupiah($variance) }}</strong>
                            </span>
                        </div>
                    </div>

                    <div class="grid gap-2">
                        @foreach ($rows as $row)
                            <div
                                class="flex flex-wrap items-center justify-between gap-2 rounded-2xl border border-line bg-[#f7f5f0] px-3.5 py-2.5">
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-ink">{{ $row->item }}
                                        <span class="ml-1 font-normal text-muted">({{ $row->category }})</span>
                                    </p>
                                    <span class="text-[10px] text-muted">
                                        Budget {{ \App\Models\PayrollRecord::formatRupiah($row->budget) }} · Actual
                                        {{ \App\Models\PayrollRecord::formatRupiah($row->actual) }}
                                        @if ($row->note)
                                            · {{ $row->note }}
                                        @endif
                                    </span>
                                </div>
                                @if (auth()->user()->canManageModule('budget'))
                                    <div class="flex gap-2">
                                        <a href="{{ route('dashboard.budget.edit', $row) }}"
                                            class="btn-wsm-white py-1.5! px-3! text-[11px]">Edit</a>
                                        <form method="POST" action="{{ route('dashboard.budget.destroy', $row) }}"
                                            data-confirm="{{ $row->item }} akan dihapus permanen."
                                            data-confirm-title="Hapus baris budget ini?" data-confirm-button="Ya, hapus"
                                            data-confirm-danger="1">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="btn-wsm-red py-1.5! px-3! text-[11px]">Hapus</button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
