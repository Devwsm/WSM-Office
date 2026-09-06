{{--
    employee/leave/index.blade.php
    ---------------------------------------------------------------------
    Fase 5 — ajukan izin/cuti + riwayat pengajuan sendiri. Estimasi hari
    kerja di form (buat cek kuota) dihitung di JS cuma buat UX (disable
    tombol lebih awal) — validasi & potongan saldo yang BENERAN tetap di
    server (LeaveRequest::countWorkDays, StoreLeaveRequestRequest).
    ---------------------------------------------------------------------
--}}
@extends('layouts.employee', ['title' => 'Pengajuan', 'navActive' => 'pengajuan'])

@section('content')
    <div class="mb-5 flex items-start justify-between gap-3">
        <div>
            <h2 class="text-[30px] font-black leading-none tracking-tight">Pengajuan Izin/Cuti</h2>
            <p class="mt-1.5 text-xs text-muted">Diajukan ke atasan langsung kamu, atau Owner kalau kamu gak punya
                atasan.</p>
        </div>
        <a href="{{ route('employee.overtime.index') }}" class="btn-wsm-white flex-none py-2! px-3! text-[11px]!">Lembur →</a>
    </div>

    <div class="stat-wsm-blue mb-4">
        <span class="stat-wsm-label">Sisa Cuti Tahunan</span>
        <div>
            <strong class="stat-wsm-value">{{ $remainingLeave }}</strong>
            <p class="stat-wsm-note mt-1">dari jatah {{ $entitlement }} hari kerja/tahun</p>
        </div>
    </div>

    {{-- Form ajukan --}}
    <div class="card-wsm-white mb-6" x-data="{
        type: 'cuti_tahunan',
        start: '',
        end: '',
        get workDays() {
            if (!this.start || !this.end) return 0;
            const s = new Date(this.start),
                e = new Date(this.end);
            if (e < s) return 0;
            let count = 0;
            for (let d = new Date(s); d <= e; d.setDate(d.getDate() + 1)) {
                const day = d.getDay();
                if (day !== 0 && day !== 6) count++;
            }
            return count;
        },
        get exceedsQuota() {
            return this.type === 'cuti_tahunan' && this.workDays > {{ $remainingLeave }};
        },
    }">
        <p class="mb-3.5 text-xs font-extrabold uppercase tracking-wide text-[#5e5952]">Ajukan Baru</p>
        <form method="POST" action="{{ route('employee.leave.store') }}" class="grid gap-3">
            @csrf
            <select name="type" x-model="type" class="input-wsm">
                <option value="cuti_tahunan">Cuti Tahunan</option>
                <option value="izin_sakit">Izin Sakit</option>
                <option value="izin_pribadi">Izin Pribadi</option>
                <option value="lainnya">Lainnya</option>
            </select>
            <div class="grid grid-cols-2 gap-3">
                <input type="date" name="start_date" x-model="start" min="{{ now()->toDateString() }}" class="input-wsm"
                    required>
                <input type="date" name="end_date" x-model="end" :min="start || '{{ now()->toDateString() }}'"
                    class="input-wsm" required>
            </div>
            <p class="text-[11px] text-muted" x-show="start && end">
                Estimasi <strong x-text="workDays"></strong> hari kerja.
                <span class="font-extrabold text-[#a83d35]" x-show="exceedsQuota">Melebihi sisa cuti tahunan kamu
                    ({{ $remainingLeave }} hari)!</span>
            </p>
            <textarea name="reason" rows="3" placeholder="Alasan pengajuan..." class="input-wsm" required></textarea>
            <button type="submit" :disabled="exceedsQuota"
                class="btn-wsm-black disabled:cursor-not-allowed disabled:opacity-40">
                Kirim Pengajuan
            </button>
        </form>
    </div>

    {{-- Riwayat --}}
    <p class="mb-2.5 text-xs font-extrabold uppercase tracking-wide text-[#5e5952]">Riwayat Pengajuan</p>
    @if ($rows->isEmpty())
        <div class="card-wsm-white text-center">
            <p class="text-xs text-muted">Belum ada pengajuan.</p>
        </div>
    @else
        <div class="grid gap-2.5">
            @foreach ($rows as $row)
                <div class="rounded-wsm border border-line bg-white p-4" x-data="{ showCancel: false }">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <strong class="block text-sm">{{ $row->typeLabel() }}</strong>
                            <span class="mt-0.5 block text-[11px] text-muted">
                                {{ $row->start_date->translatedFormat('d M Y') }}
                                @if (!$row->start_date->isSameDay($row->end_date))
                                    – {{ $row->end_date->translatedFormat('d M Y') }}
                                @endif
                                · {{ $row->work_days }} hari kerja
                            </span>
                            <p class="mt-1.5 text-xs text-ink">{{ $row->reason }}</p>

                            @if ($row->status === 'ditolak' && $row->decision_note)
                                <p class="mt-2 rounded-xl bg-[#fff0ee] p-2.5 text-[11px] text-[#a83d35]">
                                    <strong>Alasan ditolak:</strong> {{ $row->decision_note }}
                                </p>
                            @endif
                            @if ($row->status === 'dibatalkan')
                                <p class="mt-2 rounded-xl bg-[#f2f0eb] p-2.5 text-[11px] text-[#5e5952]">
                                    <strong>Dibatalkan oleh {{ $row->canceller?->name ?? '-' }}:</strong>
                                    {{ $row->cancellation_reason }}
                                </p>
                            @endif
                            @if ($row->status === 'disetujui')
                                <p class="mt-1.5 text-[11px] text-muted">Disetujui oleh
                                    {{ $row->approver?->name ?? '-' }}</p>
                            @endif
                        </div>
                        <span class="{{ $row->statusBadgeClass() }} flex-none">{{ $row->statusLabel() }}</span>
                    </div>

                    @if ($row->isCancellable())
                        <div class="mt-3 border-t border-[#eee8df] pt-3">
                            <button type="button" x-show="!showCancel" @click="showCancel = true"
                                class="text-[11px] font-extrabold text-[#a83d35]">
                                Batalkan pengajuan
                            </button>
                            <form x-show="showCancel" method="POST" action="{{ route('employee.leave.cancel', $row) }}"
                                class="grid gap-2"
                                data-confirm="Pengajuan ini akan dibatalkan dan gak bisa diaktifkan lagi."
                                data-confirm-title="Batalkan pengajuan?" data-confirm-button="Ya, batalkan"
                                data-confirm-danger="1">
                                @csrf
                                <textarea name="cancellation_reason" rows="2" placeholder="Alasan pembatalan..." class="input-wsm text-xs!"
                                    required></textarea>
                                <div class="flex gap-2">
                                    <button type="button" @click="showCancel = false"
                                        class="btn-wsm-white py-2! text-[11px]!">Batal</button>
                                    <button type="submit" class="btn-wsm-red py-2! text-[11px]!">Ya, Batalkan
                                        Pengajuan</button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
@endsection
