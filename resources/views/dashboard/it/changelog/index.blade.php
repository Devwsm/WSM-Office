{{--
    dashboard/it/changelog/index.blade.php
    ---------------------------------------------------------------------
    Fase 15 — listing SystemChangelog, filter per status
    (Planned/Released) — sama pola persis filter status di
    dashboard/royalty/index. Tombol tambah/edit/hapus cuma kelihatan
    kalau canManageModule('it').
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'System Changelog', 'navActive' => 'modules'])

@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3.5">
        <div>
            <a href="{{ route('dashboard.it.index') }}" class="text-[11px] font-extrabold text-muted">← Audit Log</a>
            <h2 class="mt-2 text-[36px] font-black leading-[0.98] tracking-tight">System Changelog</h2>
            <p class="mt-1 text-[13px] text-muted">Catatan rilis fitur buat Owner & tim.</p>
        </div>
        @if (auth()->user()->canManageModule('it'))
            <a href="{{ route('dashboard.it.changelog.create') }}" class="btn-wsm-black">+ Tambah Changelog</a>
        @endif
    </div>

    {{-- Tab balik ke Audit Log — sama komponen tab kayak dashboard/it/index. --}}
    <div class="mb-5 flex gap-2">
        <a href="{{ route('dashboard.it.index') }}"
            class="rounded-2xl border border-line bg-white px-3.5 py-2 text-[11px] font-extrabold text-ink">Audit
            Log</a>
        <span class="rounded-2xl bg-ink px-3.5 py-2 text-[11px] font-extrabold text-white">System Changelog</span>
    </div>

    <form method="GET" class="mb-4 flex flex-wrap items-center gap-2">
        <select name="status" onchange="this.form.submit()"
            class="rounded-2xl border border-line bg-white px-3.5 py-2 text-[11px] font-bold text-ink">
            <option value="">Semua Status</option>
            @foreach (\App\Models\SystemChangelog::STATUSES as $status)
                <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ $status }}</option>
            @endforeach
        </select>
    </form>

    @if ($changelogs->isEmpty())
        <div class="card-wsm-white text-center">
            <p class="text-xs text-muted">Belum ada changelog dicatat.</p>
        </div>
    @else
        <div class="grid gap-3">
            @foreach ($changelogs as $changelog)
                <div class="rounded-wsm border border-line bg-white p-4.5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="rounded-full bg-[#f2f0eb] px-2.5 py-1 text-[10px] font-extrabold text-[#5e5951]">{{ $changelog->version }}</span>
                                <span
                                    class="badge-wsm-{{ $changelog->status === 'Released' ? 'green' : 'yellow' }}">{{ $changelog->status }}</span>
                                @foreach ($changelog->modules ?? [] as $mod)
                                    <span class="badge-wsm-gray">{{ $mod }}</span>
                                @endforeach
                            </div>
                            <strong class="mt-1 block text-sm">{{ $changelog->title }}</strong>
                            <span class="text-[10px] text-muted">
                                {{ $changelog->release_date->translatedFormat('d M Y') }} · dicatat
                                {{ $changelog->creator?->name ?? 'Sistem' }}
                            </span>
                            @if (!empty($changelog->changes))
                                <ul class="mt-1.5 list-disc pl-4 text-xs text-ink">
                                    @foreach ($changelog->changes as $point)
                                        <li>{{ $point }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>

                    @if (auth()->user()->canManageModule('it'))
                        <div class="mt-3.5 flex gap-2 border-t border-[#eee8df] pt-3.5">
                            <a href="{{ route('dashboard.it.changelog.edit', $changelog) }}"
                                class="btn-wsm-white py-2! px-3.5! text-xs">Edit</a>
                            <form method="POST" action="{{ route('dashboard.it.changelog.destroy', $changelog) }}"
                                data-confirm="Changelog {{ $changelog->version }} akan dihapus permanen."
                                data-confirm-title="Hapus changelog ini?" data-confirm-button="Ya, hapus"
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

        <div class="mt-5">{{ $changelogs->links() }}</div>
    @endif
@endsection
