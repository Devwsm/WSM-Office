{{--
    employee/overtime/index.blade.php
    ---------------------------------------------------------------------
    Fase 7 — ajukan Lembur + riwayat pengajuan sendiri. Struktur & style
    SENGAJA disamain persis employee/leave/index.blade.php (Fase 5) biar
    konsisten — cuma field-nya lebih simpel (1 tanggal, bukan rentang).
    ---------------------------------------------------------------------
--}}
@extends('layouts.employee', ['title' => 'Lembur', 'navActive' => 'lembur'])

@section('content')
    <div class="mb-5 flex items-start justify-between gap-3">
        <div>
            <h2 class="text-[30px] font-black leading-none tracking-tight">Pengajuan Lembur</h2>
            <p class="mt-1.5 text-xs text-muted">Diajukan ke atasan langsung kamu, atau Owner kalau kamu gak punya
                atasan. Lembur yang disetujui bikin jam kerja hari itu gak kena batas window kerja normal
                (09:30–20:00) & gak dihitung "Kurang Jam Kerja".</p>
        </div>
        <a href="{{ route('employee.leave.index') }}" class="btn-wsm-white flex-none py-2! px-3! text-[11px]!">Izin/Cuti →</a>
    </div>

    {{-- Form ajukan --}}
    <div class="card-wsm-white mb-6">
        <p class="mb-3.5 text-xs font-extrabold uppercase tracking-wide text-[#5e5952]">Ajukan Baru</p>
        <form method="POST" action="{{ route('employee.overtime.store') }}" class="grid gap-3">
            @csrf
            <input type="date" name="date" min="{{ now()->toDateString() }}" class="input-wsm" required>
            <textarea name="reason" rows="3" placeholder="Alasan lembur..." class="input-wsm" required></textarea>
            <button type="submit" class="btn-wsm-black">
                Kirim Pengajuan
            </button>
        </form>
    </div>

    {{-- Riwayat --}}
    <p class="mb-2.5 text-xs font-extrabold uppercase tracking-wide text-[#5e5952]">Riwayat Pengajuan</p>
    @if ($rows->isEmpty())
        <div class="card-wsm-white text-center">
            <p class="text-xs text-muted">Belum ada pengajuan lembur.</p>
        </div>
    @else
        <div class="grid gap-2.5">
            @foreach ($rows as $row)
                <div class="rounded-wsm border border-line bg-white p-4" x-data="{ showCancel: false }">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <strong class="block text-sm">{{ $row->date->translatedFormat('d M Y') }}</strong>
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
                            <form x-show="showCancel" method="POST" action="{{ route('employee.overtime.cancel', $row) }}"
                                class="grid gap-2"
                                data-confirm="Pengajuan lembur ini akan dibatalkan dan gak bisa diaktifkan lagi."
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
