{{--
    dashboard/contracts/index.blade.php
    ---------------------------------------------------------------------
    Fase 11 — listing kontrak karyawan. Badge "Segera Berakhir" pakai
    EmployeeContract::isExpiringSoon() (≤30 hari, padanan logic
    `expiring` di prototype). Tombol tambah/edit/hapus cuma kelihatan
    kalau canManageModule('contracts') — sama pola kayak modul lain.
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Contract Monitoring', 'navActive' => 'modules'])

@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3.5">
        <div>
            <a href="{{ route('dashboard.index') }}" class="text-[11px] font-extrabold text-muted">← Dashboard</a>
            <h2 class="mt-2 text-[36px] font-black leading-[0.98] tracking-tight">Contract Monitoring</h2>
            <p class="mt-1 text-[13px] text-muted">Kontrak kerja karyawan — file, masa berlaku, catatan.</p>
        </div>
        @if (auth()->user()->canManageModule('contracts'))
            <a href="{{ route('dashboard.contracts.create') }}" class="btn-wsm-black">+ Upload Kontrak</a>
        @endif
    </div>

    <form method="GET" class="mb-4 flex flex-wrap items-center gap-2">
        <select name="employee_id" onchange="this.form.submit()"
            class="rounded-2xl border border-line bg-white px-3.5 py-2 text-[11px] font-bold text-ink">
            <option value="">Semua Karyawan</option>
            @foreach ($employees as $employee)
                <option value="{{ $employee->id }}" @selected($selectedEmployeeId === $employee->id)>
                    {{ $employee->name }}
                </option>
            @endforeach
        </select>
    </form>

    @if ($contracts->isEmpty())
        <div class="card-wsm-white text-center">
            <p class="text-xs text-muted">Belum ada kontrak diupload.</p>
        </div>
    @else
        <div class="grid gap-3">
            @foreach ($contracts as $contract)
                <div class="rounded-wsm border border-line bg-white p-4.5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <strong class="text-sm">{{ $contract->employee->name }}</strong>
                                @if ($contract->isExpiringSoon())
                                    <span class="badge-wsm-yellow">Segera Berakhir</span>
                                @endif
                            </div>
                            <a href="{{ asset('storage/' . $contract->file_path) }}" target="_blank"
                                class="mt-1 block text-xs font-bold text-ink underline">
                                {{ $contract->original_filename }}
                            </a>
                            <span class="text-[10px] text-muted">
                                {{ $contract->formattedSize() }} ·
                                {{ $contract->start_date?->translatedFormat('d M Y') ?? '-' }}
                                s/d
                                {{ $contract->end_date?->translatedFormat('d M Y') ?? '-' }}
                                · diupload {{ $contract->uploader->name }}
                            </span>
                            @if ($contract->notes)
                                <p class="mt-1.5 text-xs text-ink">{{ $contract->notes }}</p>
                            @endif
                        </div>
                    </div>

                    @if (auth()->user()->canManageModule('contracts'))
                        <div class="mt-3.5 flex gap-2 border-t border-[#eee8df] pt-3.5">
                            <a href="{{ route('dashboard.contracts.edit', $contract) }}"
                                class="btn-wsm-white py-2! px-3.5! text-xs">Edit</a>
                            <form method="POST" action="{{ route('dashboard.contracts.destroy', $contract) }}"
                                data-confirm="Kontrak {{ $contract->employee->name }} akan dihapus permanen."
                                data-confirm-title="Hapus kontrak ini?" data-confirm-button="Ya, hapus"
                                data-confirm-danger="1">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-wsm-red py-2! px-3.5! text-xs">Hapus</button>
                            </form>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-5">{{ $contracts->links() }}</div>
    @endif
@endsection
