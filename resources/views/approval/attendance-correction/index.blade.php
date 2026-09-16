{{--
    approval/attendance-correction/index.blade.php
    ---------------------------------------------------------------------
    2026-09-16 — Manajer lihat pengajuan bawahan LANGSUNG aja, Owner lihat
    semua (lihat Approval\AttendanceCorrectionRequestController::index()).
    Tab filter status via query string, default 'pending' — struktur
    SENGAJA disamain persis approval/leave/index.blade.php.
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Persetujuan Koreksi Presensi', 'navActive' => 'approval'])

@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3.5">
        <div>
            <h2 class="text-[36px] font-black leading-[0.98] tracking-tight">Persetujuan Koreksi Presensi</h2>
            <p class="mt-1 text-[13px] text-muted">
                @if (auth()->user()->isOwner())
                    Menampilkan semua pengajuan karyawan.
                @else
                    Menampilkan pengajuan bawahan langsung kamu.
                @endif
                Kalau disetujui, jam yang diminta langsung diterapkan ke data presensi.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('approval.leave.index') }}" class="btn-wsm-white py-2! px-3! text-[11px]!">Persetujuan
                Izin/Cuti →</a>
            <a href="{{ route('approval.overtime.index') }}" class="btn-wsm-white py-2! px-3! text-[11px]!">Persetujuan
                Lembur →</a>
        </div>
    </div>
    <div class="mb-5 flex flex-wrap gap-1.5">
        @foreach (['pending' => 'Pending', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak', 'dibatalkan' => 'Dibatalkan', 'semua' => 'Semua'] as $key => $label)
            <a href="{{ route('approval.attendanceCorrection.index', ['status' => $key]) }}"
                class="rounded-full px-3.5 py-2 text-xs font-extrabold {{ $status === $key ? 'bg-ink text-white' : 'bg-white text-[#5e5951] border border-line' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    @if ($rows->isEmpty())
        <div class="card-wsm-white text-center">
            <p class="text-xs text-muted">Tidak ada pengajuan di status ini.</p>
        </div>
    @else
        <div class="grid gap-3">
            @foreach ($rows as $row)
                <div class="rounded-wsm border border-line bg-white p-4.5" x-data="{ showReject: false }">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <strong class="block text-sm">{{ $row->user->name }}</strong>
                            <span class="text-[10px] text-muted">{{ $row->user->division ?? '-' }} ·
                                {{ $row->user->job_title ?? '-' }}</span>
                            <p class="mt-1.5 text-xs font-extrabold">{{ $row->date->translatedFormat('d M Y') }} —
                                {{ $row->requestedTimeLabel() }} ({{ $row->modeLabel() }})
                            </p>
                            <p class="mt-1 text-xs text-ink">{{ $row->reason }}</p>

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
                                    {{ $row->approver?->name ?? '-' }} · sudah diterapkan ke presensi</p>
                            @endif
                        </div>
                        <span class="{{ $row->statusBadgeClass() }} flex-none">{{ $row->statusLabel() }}</span>
                    </div>

                    @if ($row->isPending())
                        <div class="mt-3.5 flex flex-wrap gap-2 border-t border-[#eee8df] pt-3.5">
                            <form method="POST" action="{{ route('approval.attendanceCorrection.approve', $row) }}"
                                data-confirm="Jam yang diminta akan langsung diterapkan ke data presensi karyawan ini."
                                data-confirm-title="Setujui koreksi ini?" data-confirm-button="Ya, setujui">
                                @csrf
                                <button type="submit" class="btn-wsm-black py-2.5! text-xs!">Setujui</button>
                            </form>
                            <button type="button" x-show="!showReject" @click="showReject = true"
                                class="btn-wsm-white py-2.5! text-xs!">Tolak</button>
                        </div>
                        <form x-show="showReject" method="POST"
                            action="{{ route('approval.attendanceCorrection.reject', $row) }}" class="mt-3 grid gap-2"
                            data-confirm="Karyawan akan melihat alasan penolakan ini."
                            data-confirm-title="Tolak pengajuan ini?" data-confirm-button="Ya, tolak"
                            data-confirm-danger="1">
                            @csrf
                            <textarea name="decision_note" rows="2" placeholder="Alasan penolakan (wajib)..." class="input-wsm text-xs!"
                                required></textarea>
                            <div class="flex gap-2">
                                <button type="button" @click="showReject = false"
                                    class="btn-wsm-white py-2! text-[11px]!">Batal</button>
                                <button type="submit" class="btn-wsm-red py-2! text-[11px]!">Ya, Tolak
                                    Pengajuan</button>
                            </div>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
@endsection
