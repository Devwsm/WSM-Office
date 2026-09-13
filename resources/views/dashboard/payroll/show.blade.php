{{--
    dashboard/payroll/show.blade.php
    ---------------------------------------------------------------------
    Fase 12 — detail/"slip" payroll 1 karyawan 1 periode. Breakdown
    ditampilkan lebih lengkap dari `payroll_records` sendiri (jumlah
    lembur & blok shortage mentah juga ditampilkan, bukan cuma nominal
    akhirnya) biar Owner bisa nelusuri dari mana angka `overtime_amount`/
    `shortage_deduction` itu keluar — INI bagian yang README bilang
    "sengaja didesain beda dari prototype" karena prototype gak pernah
    punya breakdown UI (cuma angka ringkasan `thp`).

    Form penyesuaian (other_adjustment/notes) & tombol aksi status cuma
    kelihatan sesuai kondisi: edit hanya kalau masih draft & canManage,
    "Finalisasi" hanya draft, "Tandai Dibayar" hanya finalized, "Hapus"
    hanya draft.
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Detail Payroll', 'navActive' => 'modules'])

@section('content')
    <div class="mb-5">
        <a href="{{ route('dashboard.payroll.index', ['period' => $payroll->period]) }}"
            class="text-[11px] font-extrabold text-muted">← Payroll Overview</a>
        <div class="mt-2 flex flex-wrap items-center gap-2.5">
            <h2 class="text-[36px] font-black leading-[0.98] tracking-tight">{{ $payroll->user->name }}</h2>
            <span class="{{ $payroll->statusBadgeClass() }}">{{ $payroll->statusLabel() }}</span>
        </div>
        <p class="mt-1 text-[13px] text-muted">Periode {{ $payroll->periodLabel() }} · digenerate oleh
            {{ $payroll->generator->name }}</p>
    </div>

    <div class="grid gap-3.5 lg:grid-cols-[1.2fr_1fr]">
        <div class="card-wsm-white">
            <p class="mb-3.5 text-xs font-extrabold uppercase tracking-wide text-[#5e5952]">Breakdown</p>
            <div class="grid gap-2.5 text-sm">
                <div class="flex items-center justify-between border-b border-[#eee8df] pb-2.5">
                    <span class="text-muted">Gaji Pokok</span>
                    <strong>{{ \App\Models\PayrollRecord::formatRupiah($payroll->base_salary) }}</strong>
                </div>
                <div class="flex items-center justify-between border-b border-[#eee8df] pb-2.5">
                    <span class="text-muted">
                        Lembur
                        <span class="block text-[11px]">{{ $overtimeCount }} pengajuan disetujui ×
                            {{ \App\Models\PayrollRecord::formatRupiah($payroll->user->flat_overtime_rate) }}</span>
                    </span>
                    <strong
                        class="text-[#3d7a4d]">+{{ \App\Models\PayrollRecord::formatRupiah($payroll->overtime_amount) }}</strong>
                </div>
                <div class="flex items-center justify-between border-b border-[#eee8df] pb-2.5">
                    <span class="text-muted">
                        Potongan Kurang Jam
                        <span class="block text-[11px]">{{ $shortage['blocks'] }} blok (sisa
                            {{ $shortage['remainder_minutes'] }} menit dibawa bulan depan) ×
                            {{ \App\Models\PayrollRecord::formatRupiah($shortageRate) }}</span>
                    </span>
                    <strong
                        class="text-[#a83d35]">-{{ \App\Models\PayrollRecord::formatRupiah($payroll->shortage_deduction) }}</strong>
                </div>
                <div class="flex items-center justify-between border-b border-[#eee8df] pb-2.5">
                    <span class="text-muted">Penyesuaian Lain</span>
                    <strong>{{ \App\Models\PayrollRecord::formatRupiah($payroll->other_adjustment) }}</strong>
                </div>
                <div class="flex items-center justify-between pt-1">
                    <span class="text-sm font-extrabold">Total</span>
                    <strong class="text-lg">{{ \App\Models\PayrollRecord::formatRupiah($payroll->total) }}</strong>
                </div>
                @if ($payroll->notes)
                    <p class="mt-2 rounded-wsm border border-line bg-[#faf8f3] p-3 text-xs">{{ $payroll->notes }}</p>
                @endif
            </div>
        </div>

        <div class="grid gap-3.5">
            @if ($payroll->status === 'draft' && auth()->user()->canManageModule('payroll'))
                <div class="card-wsm-white">
                    <p class="mb-3.5 text-xs font-extrabold uppercase tracking-wide text-[#5e5952]">Penyesuaian Manual
                    </p>
                    <form method="POST" action="{{ route('dashboard.payroll.update', $payroll) }}" class="grid gap-3">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label class="mb-1 block text-[11px] font-bold text-muted">Penyesuaian Lain (Rp, boleh
                                minus)</label>
                            <input type="number" name="other_adjustment" step="1000"
                                value="{{ old('other_adjustment', $payroll->other_adjustment) }}" class="input-wsm">
                            @error('other_adjustment')
                                <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-bold text-muted">Catatan</label>
                            <textarea name="notes" rows="2" class="input-wsm">{{ old('notes', $payroll->notes) }}</textarea>
                            @error('notes')
                                <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" class="btn-wsm-black justify-self-start">Simpan Penyesuaian</button>
                    </form>
                </div>
            @endif

            @if (auth()->user()->canManageModule('payroll'))
                <div class="card-wsm-white">
                    <p class="mb-3.5 text-xs font-extrabold uppercase tracking-wide text-[#5e5952]">Aksi</p>
                    <div class="grid gap-2.5">
                        @if ($payroll->status === 'draft')
                            <form method="POST" action="{{ route('dashboard.payroll.finalize', $payroll) }}"
                                data-confirm="Setelah difinalisasi, angka payroll {{ $payroll->user->name }} periode {{ $payroll->periodLabel() }} terkunci — gak bisa digenerate ulang atau diedit lagi."
                                data-confirm-title="Finalisasi payroll ini?" data-confirm-button="Ya, finalisasi">
                                @csrf
                                <button type="submit" class="btn-wsm-black w-full">Finalisasi</button>
                            </form>
                            <form method="POST" action="{{ route('dashboard.payroll.destroy', $payroll) }}"
                                data-confirm="Payroll draft {{ $payroll->user->name }} periode {{ $payroll->periodLabel() }} akan dihapus."
                                data-confirm-title="Hapus payroll draft ini?" data-confirm-button="Ya, hapus"
                                data-confirm-danger="1">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-wsm-red w-full">Hapus Draft</button>
                            </form>
                        @elseif ($payroll->status === 'finalized')
                            <form method="POST" action="{{ route('dashboard.payroll.mark-paid', $payroll) }}"
                                data-confirm="Payroll {{ $payroll->user->name }} periode {{ $payroll->periodLabel() }} ditandai sudah dibayar."
                                data-confirm-title="Tandai sudah dibayar?" data-confirm-button="Ya, tandai dibayar">
                                @csrf
                                <button type="submit" class="btn-wsm-black w-full">Tandai Sudah Dibayar</button>
                            </form>
                        @else
                            <p class="text-xs text-muted">Payroll ini sudah dibayar — histori permanen, gak ada aksi
                                lanjutan.</p>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
