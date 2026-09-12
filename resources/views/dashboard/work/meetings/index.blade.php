{{--
    dashboard/work/meetings/index.blade.php
    ---------------------------------------------------------------------
    Fase 9 lanjutan — listing MoM terstruktur (Meeting + action items).
    Tab ke-3 di "Work Control", sebelahan MoM & Memo (ringkas) dan Work
    Tracker (board). Tombol tambah/edit/hapus/blast cuma kelihatan kalau
    canManageModule('work') — sama pola kayak dashboard/work/index.
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Work Control — Rapat & Action Item', 'navActive' => 'modules'])

@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3.5">
        <div>
            <a href="{{ route('dashboard.work.index') }}" class="text-[11px] font-extrabold text-muted">←
                Dashboard</a>
            <h2 class="mt-2 text-[36px] font-black leading-[0.98] tracking-tight">Rapat & Action Item</h2>
            <p class="mt-1 text-[13px] text-muted">MoM terstruktur — attendee asli, action item per-PIC, bisa
                di-generate ke Work Tracker.</p>
        </div>
        @if (auth()->user()->canManageModule('work'))
            <a href="{{ route('dashboard.work.meetings.create') }}" class="btn-wsm-black">+ Tambah MoM</a>
        @endif
    </div>

    <div class="mb-5 flex gap-2">
        <a href="{{ route('dashboard.work.index') }}"
            class="rounded-2xl border border-line bg-white px-3.5 py-2 text-[11px] font-extrabold text-ink">MoM
            &amp; Memo</a>
        <a href="{{ route('dashboard.work.tracker.index') }}"
            class="rounded-2xl border border-line bg-white px-3.5 py-2 text-[11px] font-extrabold text-ink">Work
            Tracker</a>
        <span class="rounded-2xl bg-ink px-3.5 py-2 text-[11px] font-extrabold text-white">Rapat & Action Item</span>
    </div>

    @if ($meetings->isEmpty())
        <div class="card-wsm-white text-center">
            <p class="text-xs text-muted">Belum ada MoM terstruktur.</p>
        </div>
    @else
        <div class="grid gap-3">
            @foreach ($meetings as $meeting)
                <div class="rounded-wsm border border-line bg-white p-4.5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="rounded-full bg-[#f2f0eb] px-2.5 py-1 text-[10px] font-extrabold text-[#5e5951]">
                                    {{ $meeting->date->translatedFormat('d M Y') }}
                                    @if ($meeting->time)
                                        · {{ substr($meeting->time, 0, 5) }}
                                    @endif
                                </span>
                                @if ($meeting->project)
                                    <span
                                        class="rounded-full bg-[#e8f0ff] px-2.5 py-1 text-[10px] font-extrabold text-[#3158a8]">
                                        {{ $meeting->project->name }}
                                    </span>
                                @endif
                                @if ($meeting->wasBlasted())
                                    <span
                                        class="rounded-full bg-[#e6f4e9] px-2.5 py-1 text-[10px] font-extrabold text-[#1c6c39]">
                                        Sudah di-blast
                                    </span>
                                @endif
                            </div>
                            <a href="{{ route('dashboard.work.meetings.show', $meeting) }}"
                                class="mt-1.5 block text-sm font-black text-ink">{{ $meeting->agenda }}</a>
                            <span class="text-[10px] text-muted">
                                {{ $meeting->creator->name }} ·
                                {{ $meeting->actionItems->count() }} action item ·
                                {{ $meeting->attendees->count() }} peserta tim
                                @if ($meeting->persons_text)
                                    · {{ $meeting->persons_text }}
                                @endif
                            </span>
                        </div>
                    </div>

                    @if (auth()->user()->canManageModule('work'))
                        <div class="mt-3.5 flex flex-wrap gap-2 border-t border-[#eee8df] pt-3.5">
                            <a href="{{ route('dashboard.work.meetings.show', $meeting) }}"
                                class="btn-wsm-white py-2! px-3.5! text-xs">Detail</a>
                            <a href="{{ route('dashboard.work.meetings.edit', $meeting) }}"
                                class="btn-wsm-white py-2! px-3.5! text-xs">Edit</a>
                            <form method="POST" action="{{ route('dashboard.work.meetings.blast', $meeting) }}">
                                @csrf
                                <button type="submit" class="btn-wsm-white py-2! px-3.5! text-xs">
                                    {{ $meeting->wasBlasted() ? 'Blast Ulang' : 'Blast Summary' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('dashboard.work.meetings.destroy', $meeting) }}"
                                data-confirm="{{ $meeting->agenda }} akan dihapus permanen."
                                data-confirm-title="Hapus MoM ini?" data-confirm-button="Ya, hapus" data-confirm-danger="1">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-wsm-red py-2! px-3.5! text-xs">Hapus</button>
                            </form>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-5">{{ $meetings->links() }}</div>
    @endif
@endsection
