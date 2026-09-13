{{--
    dashboard/royalty/index.blade.php
    ---------------------------------------------------------------------
    Fase 13 — listing RoyaltyEntry, filter per status. Net payable
    dihitung live lewat RoyaltyEntry::net() — bukan kolom tersimpan.
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Royalty Dashboard', 'navActive' => 'modules'])

@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3.5">
        <div>
            <a href="{{ route('dashboard.index') }}" class="text-[11px] font-extrabold text-muted">← Dashboard</a>
            <h2 class="mt-2 text-[36px] font-black leading-[0.98] tracking-tight">Royalty Dashboard</h2>
            <p class="mt-1 text-[13px] text-muted">Royalty, share, recoupment, status pembayaran.</p>
        </div>
        @if (auth()->user()->canManageModule('royalty'))
            <a href="{{ route('dashboard.royalty.create') }}" class="btn-wsm-black">+ Tambah Entry</a>
        @endif
    </div>

    <form method="GET" class="mb-4 flex flex-wrap items-center gap-2">
        <select name="status" onchange="this.form.submit()"
            class="rounded-2xl border border-line bg-white px-3.5 py-2 text-[11px] font-bold text-ink">
            <option value="">Semua Status</option>
            @foreach (\App\Models\RoyaltyEntry::STATUSES as $status)
                <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ $status }}</option>
            @endforeach
        </select>
    </form>

    @if ($entries->isEmpty())
        <div class="card-wsm-white text-center">
            <p class="text-xs text-muted">Belum ada royalty entry.</p>
        </div>
    @else
        <div class="grid gap-3">
            @foreach ($entries as $entry)
                <div class="rounded-wsm border border-line bg-white p-4.5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($entry->period)
                                    <span
                                        class="rounded-full bg-[#f2f0eb] px-2.5 py-1 text-[10px] font-extrabold text-[#5e5951]">
                                        {{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $entry->period)->translatedFormat('F Y') }}
                                    </span>
                                @endif
                                <span
                                    class="badge-wsm-{{ match ($entry->status) {
                                        'Paid' => 'green',
                                        'Ready to Pay' => 'blue',
                                        'Reported' => 'yellow',
                                        default => 'gray',
                                    } }}">{{ $entry->status }}</span>
                            </div>
                            <strong class="mt-1 block text-sm">{{ $entry->title }}</strong>
                            <span class="text-[10px] text-muted">
                                {{ $entry->source ?? 'Sumber tidak diisi' }} ·
                                Gross {{ \App\Models\PayrollRecord::formatRupiah($entry->gross) }} ·
                                Share {{ $entry->share_pct }}% ·
                                Recoup {{ \App\Models\PayrollRecord::formatRupiah($entry->recoup) }}
                            </span>
                            @if ($entry->note)
                                <p class="mt-1.5 text-xs text-ink">{{ $entry->note }}</p>
                            @endif
                        </div>
                        <div class="flex-none text-right">
                            <span class="block text-[10px] font-extrabold uppercase text-muted">Net Payable</span>
                            <strong class="text-sm">{{ \App\Models\PayrollRecord::formatRupiah($entry->net()) }}</strong>
                        </div>
                    </div>

                    @if (auth()->user()->canManageModule('royalty'))
                        <div class="mt-3.5 flex gap-2 border-t border-[#eee8df] pt-3.5">
                            <a href="{{ route('dashboard.royalty.edit', $entry) }}"
                                class="btn-wsm-white py-2! px-3.5! text-xs">Edit</a>
                            <form method="POST" action="{{ route('dashboard.royalty.destroy', $entry) }}"
                                data-confirm="{{ $entry->title }} akan dihapus permanen."
                                data-confirm-title="Hapus royalty entry ini?" data-confirm-button="Ya, hapus"
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

        <div class="mt-5">{{ $entries->links() }}</div>
    @endif
@endsection
