{{--
    dashboard/work/projects/index.blade.php
    ---------------------------------------------------------------------
    2026-09-28 — Menu "Projects" (Work Control). Padanan halaman
    "Project Master" prototype: daftar semua project (progress, priority,
    status, jumlah item) + tombol "Add New Project" yang membuka MODAL,
    bukan form split screen. Isi task per project dilihat di Work Tracker
    (tombol "Open Work Tracker").
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Projects', 'navActive' => 'modules'])

@section('content')
    @php
        $canManage = auth()->user()->canManageModule('work');
        $priorityClass = fn(string $p) => match ($p) {
            'High' => 'badge-wsm-red',
            'Medium' => 'badge-wsm-yellow',
            default => 'badge-wsm-gray',
        };
        $statusClass = fn(string $s) => match ($s) {
            'Done', 'Confirmed' => 'badge-wsm-green',
            'On Development' => 'badge-wsm-blue',
            'Follow Up' => 'badge-wsm-yellow',
            default => 'badge-wsm-gray',
        };
    @endphp

    <div class="mb-5 flex flex-wrap items-end justify-between gap-3.5">
        <div>
            <a href="{{ route('dashboard.work.index') }}" class="text-[11px] font-extrabold text-muted">← Work Control</a>
            <h2 class="mt-2 text-3xl font-black leading-[0.98] tracking-tight sm:text-[36px]">Projects</h2>
            <p class="mt-1 text-[13px] text-muted">Kelola project master. Task-nya dikerjakan di Work Tracker.</p>
        </div>
        @if ($canManage)
            <button type="button" onclick="window.dispatchEvent(new CustomEvent('wt-project-modal', { detail: null }))"
                class="btn-wsm-black">+ Add New Project</button>
        @endif
    </div>

    <div class="mb-4 flex items-center justify-between">
        <h3 class="text-sm font-black">All Projects</h3>
        <span class="rounded-full bg-[#ece7dd] px-3 py-1 text-[10px] font-extrabold text-muted">{{ $projects->count() }}
            project</span>
    </div>

    <div class="grid gap-3.5 xl:grid-cols-2">
        @forelse ($projects as $project)
            @php
                $pct = $project->items_total ? (int) round(($project->items_done / $project->items_total) * 100) : 0;
                $dates = collect([
                    $project->start_date?->translatedFormat('d M Y'),
                    $project->end_date?->translatedFormat('d M Y'),
                ])
                    ->filter()
                    ->implode(' – ');
            @endphp
            <article class="rounded-3xl border border-line bg-white p-4">
                <div class="flex flex-col gap-2.5 sm:flex-row sm:items-start sm:justify-between sm:gap-3">
                    <div class="min-w-0">
                        <p class="flex items-center gap-1.5 text-[9px] font-extrabold uppercase tracking-widest text-muted">
                            <span class="h-2.5 w-2.5 flex-none rounded-full"
                                style="background:{{ $project->color }}"></span>Project
                        </p>
                        <h4 class="mt-0.5 wrap-break-word text-lg font-black leading-tight">{{ $project->name }}</h4>
                        @if ($project->progress_recap)
                            <p class="mt-1 line-clamp-2 text-xs text-muted">{{ $project->progress_recap }}</p>
                        @endif
                    </div>
                    <div class="flex flex-none flex-wrap gap-1 sm:justify-end">
                        <span class="{{ $priorityClass($project->priority) }}">{{ $project->priority }}</span>
                        <span class="{{ $statusClass($project->status) }}">{{ $project->status }}</span>
                    </div>
                </div>

                <div class="mt-2.5 flex flex-wrap gap-1.5 text-[10px] font-bold text-muted">
                    @if ($dates)
                        <span class="rounded-full bg-[#f2f0eb] px-2 py-1">{{ $dates }}</span>
                    @endif
                    @if ($project->lead)
                        <span class="rounded-full bg-[#f2f0eb] px-2 py-1">Lead: {{ $project->lead->name }}</span>
                    @endif
                    <span class="rounded-full bg-[#f2f0eb] px-2 py-1">{{ $project->items_total }} item</span>
                </div>

                <div class="mt-3 flex items-center gap-2.5">
                    <span class="text-[10px] font-bold text-muted">Progress</span>
                    <div class="h-2 min-w-0 flex-1 overflow-hidden rounded-full bg-[#ece7dd]">
                        <div class="h-full rounded-full"
                            style="width:{{ $pct }}%;background:{{ $project->color }}">
                        </div>
                    </div>
                    <span class="text-sm font-black">{{ $pct }}%</span>
                </div>

                <div class="mt-3 flex flex-wrap gap-1.5">
                    @if ($canManage)
                        <button type="button" data-project="{{ $project->toJson() }}"
                            onclick="window.dispatchEvent(new CustomEvent('wt-project-modal', { detail: JSON.parse(this.dataset.project) }))"
                            class="rounded-xl border border-line bg-white px-3 py-1.5 text-[10px] font-extrabold">Edit</button>
                    @endif
                    <a href="{{ route('dashboard.work.tracker.index', ['project_id' => $project->id]) }}"
                        class="rounded-xl border border-line bg-white px-3 py-1.5 text-[10px] font-extrabold">Open Work
                        Tracker</a>
                    @if ($project->tracker_url)
                        <a href="{{ $project->tracker_url }}" target="_blank" rel="noopener"
                            class="rounded-xl border border-line bg-white px-3 py-1.5 text-[10px] font-extrabold">Tracker
                            Link ↗</a>
                    @endif
                    @if ($canManage)
                        <form method="POST" action="{{ route('dashboard.work.tracker.projects.destroy', $project) }}"
                            data-confirm="Hapus project &quot;{{ $project->name }}&quot;? Task yang nempel akan dipindah jadi Tanpa Project, bukan ikut kehapus."
                            data-confirm-title="Hapus project?" data-confirm-button="Ya, hapus">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="rounded-xl bg-[#ffded8] px-3 py-1.5 text-[10px] font-extrabold text-[#9b392f]">Hapus</button>
                        </form>
                    @endif
                </div>
            </article>
        @empty
            <p class="rounded-3xl border border-dashed border-line p-6 text-sm text-muted">Belum ada project.
                @if ($canManage)
                    Klik "+ Add New Project" untuk mulai.
                @endif
            </p>
        @endforelse
    </div>

    @if ($canManage)
        @include('dashboard.work.projects._modal')
    @endif
@endsection
