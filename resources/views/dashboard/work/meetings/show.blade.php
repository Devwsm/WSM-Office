{{--
    dashboard/work/meetings/show.blade.php
    ---------------------------------------------------------------------
    Fase 9 lanjutan — detail 1 MoM terstruktur. Action item nunjukin
    link balik ke Work Item kalau udah di-generate ke Work Tracker
    (relasi meetingActionItem->workItem, di-load controller lewat
    'actionItems.workItem').
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => $meeting->agenda, 'navActive' => 'modules'])

@section('content')
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
            <a href="{{ route('dashboard.work.meetings.index') }}" class="text-[11px] font-extrabold text-muted">←
                Rapat & Action Item</a>
            <h2 class="mt-2 text-[32px] font-black leading-[1.05] tracking-tight">{{ $meeting->agenda }}</h2>
            <p class="mt-1 text-[13px] text-muted">
                {{ $meeting->date->translatedFormat('d M Y') }}
                @if ($meeting->time)
                    · {{ substr($meeting->time, 0, 5) }}
                @endif
                @if ($meeting->project)
                    · {{ $meeting->project->name }}
                @endif
            </p>
        </div>

        @if (auth()->user()->canManageModule('work'))
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('dashboard.work.meetings.edit', $meeting) }}" class="btn-wsm-white">Edit</a>
                <form method="POST" action="{{ route('dashboard.work.meetings.blast', $meeting) }}">
                    @csrf
                    <button type="submit" class="btn-wsm-black">
                        {{ $meeting->wasBlasted() ? 'Blast Ulang' : 'Blast Summary' }}
                    </button>
                </form>
            </div>
        @endif
    </div>

    @if ($meeting->wasBlasted())
        <div class="mb-5 rounded-2xl bg-[#e6f4e9] px-3.5 py-2.5 text-xs font-semibold text-[#1c6c39]">
            Sudah di-blast jadi Memo ke semua karyawan pada
            {{ $meeting->blasted_at->translatedFormat('d M Y, H:i') }}.
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="card-wsm-white">
            <h3 class="field-label-wsm mb-2">Peserta</h3>
            @if ($meeting->attendees->isEmpty() && !$meeting->persons_text)
                <p class="text-xs text-muted">Belum ada peserta ditandai.</p>
            @else
                <p class="text-xs text-ink">
                    {{ $meeting->attendees->pluck('name')->implode(', ') ?: '-' }}
                    @if ($meeting->persons_text)
                        <br><span class="text-muted">Tambahan: {{ $meeting->persons_text }}</span>
                    @endif
                </p>
            @endif
        </div>

        <div class="card-wsm-white">
            <h3 class="field-label-wsm mb-2">Catatan</h3>
            <p class="whitespace-pre-line text-xs text-ink">{{ $meeting->notes ?: '-' }}</p>
        </div>

        <div class="card-wsm-white sm:col-span-2">
            <h3 class="field-label-wsm mb-2">Keputusan</h3>
            <p class="whitespace-pre-line text-xs text-ink">{{ $meeting->decisions ?: '-' }}</p>
        </div>
    </div>

    <div class="card-wsm-white mt-4">
        <h3 class="field-label-wsm mb-3">Action Items</h3>
        @if ($meeting->actionItems->isEmpty())
            <p class="text-xs text-muted">Belum ada action item.</p>
        @else
            <div class="grid gap-2">
                @foreach ($meeting->actionItems as $item)
                    <div
                        class="flex flex-wrap items-center justify-between gap-2 rounded-2xl border border-line bg-[#f7f5f0] px-3.5 py-2.5">
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-ink">{{ $item->task }}</p>
                            <span class="text-[10px] text-muted">
                                PIC: {{ $item->pic_all ? 'ALL TEAM' : $item->pic->name ?? 'Belum di-assign' }}
                                @if ($item->due_date)
                                    · Due {{ $item->due_date->translatedFormat('d M Y') }}
                                @endif
                            </span>
                        </div>
                        @if ($item->workItem)
                            <span class="rounded-full bg-[#e8f0ff] px-2.5 py-1 text-[10px] font-extrabold text-[#3158a8]">
                                Di Work Tracker: {{ $item->workItem->progress }}
                            </span>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
