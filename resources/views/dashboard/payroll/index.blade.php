{{--
    dashboard/payroll/index.blade.php
    ---------------------------------------------------------------------
    Fase 12 — listing payroll per periode (bulan). Beda dari kontrak/KPI
    yang paginated flat list, di sini per PERIODE (`?period=YYYY-MM`,
    default bulan berjalan) karena payroll memang siklus bulanan, bukan
    daftar yang terus bertambah. Tombol "Generate" cuma kelihatan kalau
    canManageModule('payroll') — sama pola modul lain.
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Payroll Overview', 'navActive' => 'modules'])

@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3.5">
        <div>
            <a href="{{ route('dashboard.index') }}" class="text-[11px] font-extrabold text-muted">← Dashboard</a>
            <h2 class="mt-2 text-[36px] font-black leading-[0.98] tracking-tight">Payroll Overview</h2>
            <p class="mt-1 text-[13px] text-muted">Payroll & take home pay — histori tersimpan per bulan, gak
                dihitung ulang otomatis.</p>
        </div>
        <form method="GET" class="flex items-center gap-2">
            <input type="month" name="period" value="{{ $period }}" onchange="this.form.submit()"
                class="rounded-2xl border border-line bg-white px-3.5 py-2 text-[11px] font-bold text-ink">
        </form>
    </div>

    <div class="mb-5 grid grid-cols-1 gap-3.5 sm:grid-cols-3">
        <div class="stat-wsm-blue">
            <span class="stat-wsm-label">Total Payroll —
                {{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $period)->translatedFormat('F Y') }}</span>
            <div>
                <strong class="stat-wsm-value">{{ \App\Models\PayrollRecord::formatRupiah($totalPayroll) }}</strong>
                <p class="stat-wsm-note mt-1">{{ $records->count() }} karyawan sudah digenerate</p>
            </div>
        </div>
        <div class="stat-wsm-yellow">
            <span class="stat-wsm-label">Belum Digenerate</span>
            <div>
                <strong class="stat-wsm-value">{{ $notYetGeneratedCount }}</strong>
                <p class="stat-wsm-note mt-1">karyawan bergaji yang belum ada payroll periode ini</p>
            </div>
        </div>
        <div class="stat-wsm-lime">
            <span class="stat-wsm-label">Belum Ada Gaji Pokok</span>
            <div>
                <strong class="stat-wsm-value">{{ $missingSalaryCount }}</strong>
                <p class="stat-wsm-note mt-1">
                    karyawan tanpa "Gaji Pokok" diisi —
                    <a href="{{ route('owner.employees.index') }}" class="underline">lengkapi di data karyawan</a>
                </p>
            </div>
        </div>
    </div>

    @if (auth()->user()->canManageModule('payroll'))
        <div class="card-wsm-white mb-5">
            <p class="mb-3.5 text-xs font-extrabold uppercase tracking-wide text-[#5e5952]">Generate Payroll</p>
            <form method="POST" action="{{ route('dashboard.payroll.generate') }}" class="grid gap-3">
                @csrf
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-[auto_1fr_auto] sm:items-end">
                    <div>
                        <label class="mb-1 block text-[11px] font-bold text-muted">Periode</label>
                        <input type="month" name="period" value="{{ $period }}" class="input-wsm" required>
                    </div>
                    <div>
                        <label class="mb-1 block text-[11px] font-bold text-muted">Karyawan (kosongkan = semua yang
                            bergaji)</label>
                        <select name="employee_ids[]" multiple size="4" class="input-wsm">
                            @foreach ($eligibleEmployees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn-wsm-black">Generate</button>
                </div>
                <p class="text-[11px] text-muted">Karyawan yang record-nya di periode ini sudah
                    <em>final</em>/<em>dibayar</em>
                    otomatis dilewati, gak ditimpa — regenerate cuma nyentuh yang masih <em>draft</em>.</p>
            </form>
        </div>
    @endif

    @if ($records->isEmpty())
        <div class="card-wsm-white text-center">
            <p class="text-xs text-muted">Belum ada payroll digenerate untuk periode ini.</p>
        </div>
    @else
        <div class="overflow-x-auto rounded-wsm border border-line bg-white">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#faf8f3] text-[10px] font-extrabold uppercase tracking-wide text-muted">
                    <tr>
                        <th class="px-4 py-3">Karyawan</th>
                        <th class="px-4 py-3">Gaji Pokok</th>
                        <th class="px-4 py-3">Lembur</th>
                        <th class="px-4 py-3">Potongan</th>
                        <th class="px-4 py-3">Total</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#eee8df]">
                    @foreach ($records as $record)
                        <tr>
                            <td class="px-4 py-3 font-bold">{{ $record->user->name }}</td>
                            <td class="px-4 py-3">{{ \App\Models\PayrollRecord::formatRupiah($record->base_salary) }}</td>
                            <td class="px-4 py-3">+{{ \App\Models\PayrollRecord::formatRupiah($record->overtime_amount) }}
                            </td>
                            <td class="px-4 py-3">
                                -{{ \App\Models\PayrollRecord::formatRupiah($record->shortage_deduction) }}</td>
                            <td class="px-4 py-3 font-extrabold">
                                {{ \App\Models\PayrollRecord::formatRupiah($record->total) }}</td>
                            <td class="px-4 py-3">
                                <span class="{{ $record->statusBadgeClass() }}">{{ $record->statusLabel() }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('dashboard.payroll.show', $record) }}"
                                    class="btn-wsm-white py-2! px-3.5! text-xs">Detail</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
