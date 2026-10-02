{{--
    dashboard/budget/index.blade.php
    ---------------------------------------------------------------------
    Project Budgeting — padanan halaman budget prototype v22/v23:
      1. kartu ringkasan (Total Budget, Actual, Remaining, Utilization),
      2. grafik Budget vs Actual (per kategori / project / lagu),
      3. baris budget dikelompokkan per project (tabel + subtotal),
      4. unduh PDF, Export/Import Excel (lewat Export & Import Center).

    Semua angka datang dari App\Support\BudgetReport ($totals, $chart,
    $entriesByProject) — sama persis dengan yang dipakai PDF. Variance
    negatif (melebihi budget) selalu ditandai TEKS ("Melebihi budget"),
    bukan hanya warna merah.
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Project Budgeting', 'navActive' => 'modules'])

@php
    $rp = fn($value) => \App\Models\PayrollRecord::formatRupiah($value);
    $canManage = auth()->user()->canManageModule('budget');
    // Filter project yang sedang aktif, dibawa ke semua link (toggle grafik, PDF, Export).
    $scope = array_filter(['project_id' => $selectedProjectId]);
    $pct = fn($value) => $value === null ? '–' : $value . '%';
    $remainingClass = fn($value) => $value < 0 ? 'text-[#a83d35]' : 'text-[#3d7a4d]';
@endphp

@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3.5">
        <div>
            <a href="{{ route('dashboard.index') }}" class="text-[11px] font-extrabold text-muted">← Dashboard</a>
            <h2 class="mt-2 text-[36px] font-black leading-[0.98] tracking-tight">Project Budgeting</h2>
            <p class="mt-1 text-[13px] text-muted">Budget vs actual per project.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @unless ($report->isEmpty())
                <a href="{{ route('dashboard.budget.pdf', $scope + ['group' => $group]) }}" target="_blank" rel="noopener"
                    class="btn-wsm-white">Unduh PDF</a>
            @endunless
            <a href="{{ route('dashboard.export-import.preview', ['key' => 'budget', 'format' => 'excel'] + $scope) }}"
                class="btn-wsm-white">Export Excel</a>
            @if ($canManage)
                <a href="{{ route('dashboard.export-import.import.show', ['key' => 'budget']) }}"
                    class="btn-wsm-white">Import Excel</a>
                <a href="{{ route('dashboard.budget.create') }}" class="btn-wsm-black">+ Tambah Budget</a>
            @endif
        </div>
    </div>

    <form method="GET" class="mb-4 flex flex-wrap items-center gap-2">
        <input type="hidden" name="group" value="{{ $group }}">
        <select name="project_id" onchange="this.form.submit()" aria-label="Filter project"
            class="rounded-2xl border border-line bg-white px-3.5 py-2 text-[11px] font-bold text-ink">
            <option value="">Semua Project</option>
            @foreach ($projects as $project)
                <option value="{{ $project->id }}" @selected($selectedProjectId === $project->id)>
                    {{ $project->name }}
                </option>
            @endforeach
        </select>
    </form>

    @if ($report->isEmpty())
        <div class="card-wsm-white text-center">
            <p class="text-xs text-muted">Belum ada baris budget.</p>
        </div>
    @else
        {{-- 1. Ringkasan --}}
        <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="card-wsm-white min-w-0">
                <p class="text-[10px] font-black uppercase tracking-wide text-muted">Total Budget</p>
                <p class="mt-1 text-xl font-black tracking-tight sm:text-2xl">{{ $rp($totals['budget']) }}</p>
            </div>
            <div class="card-wsm-white min-w-0">
                <p class="text-[10px] font-black uppercase tracking-wide text-muted">Actual</p>
                <p class="mt-1 text-xl font-black tracking-tight sm:text-2xl">{{ $rp($totals['actual']) }}</p>
            </div>
            <div class="card-wsm-white min-w-0">
                <p class="text-[10px] font-black uppercase tracking-wide text-muted">Remaining</p>
                <p class="mt-1 text-xl font-black tracking-tight sm:text-2xl {{ $remainingClass($totals['remaining']) }}">
                    {{ $rp($totals['remaining']) }}</p>
                @if ($totals['remaining'] < 0)
                    <p class="mt-0.5 text-[10px] font-extrabold text-[#a83d35]">Melebihi budget</p>
                @endif
            </div>
            <div class="card-wsm-white min-w-0">
                <p class="text-[10px] font-black uppercase tracking-wide text-muted">Utilization</p>
                <p
                    class="mt-1 text-xl font-black tracking-tight sm:text-2xl {{ ($totals['utilization'] ?? 0) > 100 ? 'text-[#a83d35]' : '' }}">
                    {{ $pct($totals['utilization']) }}</p>
                @if ($totals['utilization'] === null)
                    <p class="mt-0.5 text-[10px] text-muted">Belum ada budget</p>
                @endif
            </div>
        </div>

        {{-- 2. Grafik Budget vs Actual --}}
        <section class="card-wsm-white mb-5" aria-labelledby="budget-chart-title">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-muted">Budget Comparison</p>
                    <h3 id="budget-chart-title" class="text-lg font-black tracking-tight">Budget vs Actual</h3>
                </div>
                <nav aria-label="Kelompokkan grafik" class="flex flex-wrap gap-1.5">
                    @foreach ($groups as $key => $label)
                        <a href="{{ route('dashboard.budget.index', $scope + ['group' => $key]) }}"
                            @if ($group === $key) aria-current="true" @endif
                            class="rounded-full px-3.5 py-2 text-[11px] font-extrabold transition {{ $group === $key ? 'bg-ink text-white' : 'border border-line bg-white text-ink hover:bg-cream' }}">{{ $label }}</a>
                    @endforeach
                </nav>
            </div>

            <div class="grid gap-3.5">
                @foreach ($chart as $bar)
                    <div class="grid gap-1.5 sm:grid-cols-[minmax(0,170px)_1fr_minmax(0,210px)] sm:items-center sm:gap-3">
                        <div class="truncate text-[11px] font-black" title="{{ $bar['label'] }}">{{ $bar['label'] }}</div>
                        {{-- Batang hanya hiasan; angkanya dibaca dari teks di kanan. --}}
                        <div class="relative h-4.5 overflow-hidden rounded-full bg-[#eee9e0]" aria-hidden="true">
                            <div class="absolute inset-y-0 left-0 rounded-full bg-[#dfe8ff]"
                                style="width: {{ $bar['plan_pct'] }}%; min-width: {{ $bar['budget'] > 0 ? '6px' : '0' }}">
                            </div>
                            <div class="absolute inset-y-1 left-0 rounded-full {{ $bar['over'] ? 'bg-[#d4574c]' : 'bg-brand-blue' }}"
                                style="width: {{ $bar['actual_pct'] }}%; min-width: {{ $bar['actual'] > 0 ? '6px' : '0' }}">
                            </div>
                        </div>
                        <div class="text-[11px] sm:text-right">
                            <strong>{{ $rp($bar['actual']) }}</strong>
                            <span class="text-muted">/ {{ $rp($bar['budget']) }}</span>
                            <span
                                class="ml-1 font-extrabold {{ $bar['over'] ? 'text-[#a83d35]' : 'text-[#3d7a4d]' }}">{{ $pct($bar['utilization']) }}</span>
                            @if ($bar['over'])
                                <span class="badge-wsm-red ml-1">Melebihi budget</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 flex flex-wrap gap-x-4 gap-y-1 text-[10px] font-bold text-muted">
                <span class="inline-flex items-center gap-1.5"><i
                        class="inline-block h-2 w-4 rounded-full bg-[#dfe8ff]"></i>
                    Budget</span>
                <span class="inline-flex items-center gap-1.5"><i
                        class="inline-block h-2 w-4 rounded-full bg-brand-blue"></i>
                    Actual</span>
                <span class="inline-flex items-center gap-1.5"><i
                        class="inline-block h-2 w-4 rounded-full bg-[#d4574c]"></i>
                    Actual melebihi budget</span>
            </div>
        </section>

        {{-- 3. Baris budget per project --}}
        <div class="grid gap-5">
            @foreach ($entriesByProject as $projectGroup)
                <div class="rounded-wsm border border-line bg-white p-4.5">
                    <div class="mb-3.5 flex flex-wrap items-center justify-between gap-2 border-b border-[#eee8df] pb-3.5">
                        <div class="flex flex-wrap items-center gap-2">
                            <strong class="text-sm">{{ $projectGroup['project'] }}</strong>
                            @if ($projectGroup['remaining'] < 0)
                                <span class="badge-wsm-red">Melebihi budget</span>
                            @endif
                        </div>
                        <div class="flex flex-wrap gap-3 text-[11px]">
                            <span>Budget: <strong>{{ $rp($projectGroup['budget']) }}</strong></span>
                            <span>Actual: <strong>{{ $rp($projectGroup['actual']) }}</strong></span>
                            <span class="{{ $remainingClass($projectGroup['remaining']) }}">
                                Variance: <strong>{{ $rp($projectGroup['remaining']) }}</strong>
                            </span>
                            <span>Utilization: <strong>{{ $pct($projectGroup['utilization']) }}</strong></span>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-215 text-left text-xs">
                            <thead class="text-[10px] font-extrabold uppercase tracking-wide text-muted">
                                <tr>
                                    <th class="px-3 py-2">Kategori</th>
                                    <th class="px-3 py-2">Item</th>
                                    <th class="px-3 py-2">Lagu</th>
                                    <th class="px-3 py-2 text-right">Budget</th>
                                    <th class="px-3 py-2 text-right">Actual</th>
                                    <th class="px-3 py-2 text-right">Variance</th>
                                    <th class="px-3 py-2">Bukti</th>
                                    <th class="px-3 py-2">Catatan</th>
                                    @if ($canManage)
                                        <th class="px-3 py-2"><span class="sr-only">Aksi</span></th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#eee8df]">
                                @foreach ($projectGroup['rows'] as $row)
                                    @php $variance = $row->variance(); @endphp
                                    <tr>
                                        <td class="px-3 py-2.5 text-muted">{{ $row->category }}</td>
                                        <td class="px-3 py-2.5 font-bold text-ink">{{ $row->item }}</td>
                                        <td class="px-3 py-2.5">{{ $row->song_title ?: '-' }}</td>
                                        <td class="px-3 py-2.5 text-right tabular-nums">{{ $rp($row->budget) }}</td>
                                        <td class="px-3 py-2.5 text-right tabular-nums">{{ $rp($row->actual) }}</td>
                                        <td
                                            class="px-3 py-2.5 text-right font-extrabold tabular-nums {{ $remainingClass($variance) }}">
                                            {{ $rp($variance) }}</td>
                                        <td class="px-3 py-2.5">
                                            @if ($row->proof_link)
                                                <a href="{{ $row->proof_link }}" target="_blank"
                                                    rel="noopener noreferrer" class="font-extrabold underline">Buka</a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="max-w-60 px-3 py-2.5 wrap-break-word text-muted">
                                            {{ $row->note ?: '-' }}
                                        </td>
                                        @if ($canManage)
                                            <td class="px-3 py-2.5">
                                                <div class="flex justify-end gap-2">
                                                    <a href="{{ route('dashboard.budget.edit', $row) }}"
                                                        class="btn-wsm-white py-1.5! px-3! text-[11px]">Edit</a>
                                                    <form method="POST"
                                                        action="{{ route('dashboard.budget.destroy', $row) }}"
                                                        data-confirm="{{ $row->item }} akan dihapus permanen."
                                                        data-confirm-title="Hapus baris budget ini?"
                                                        data-confirm-button="Ya, hapus" data-confirm-danger="1">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="btn-wsm-red py-1.5! px-3! text-[11px]">Hapus</button>
                                                    </form>
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
