{{--
    dashboard/export-import/index.blade.php
    -----------------------------------------------------------------
    Batch 0/1 — kartu per modul dari ExportCatalog::visibleFor(). Tiap
    FORMAT (excel/pdf) dicek independen ke $entry['implemented_exports']
    — makanya 1 kartu bisa punya tombol "Export Excel" yang aktif
    BARENGAN "Export PDF" yang masih "Segera Hadir" (mis. Rekap
    Absensi: Excel Batch 1, PDF Batch 2). Import juga dicek terpisah
    lewat 'import_implemented'.
    -----------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Export & Import', 'navActive' => 'export-import'])

@section('content')
    <div class="mb-6">
        <h2 class="text-[36px] font-black leading-[0.98] tracking-tight">Export &amp; Import</h2>
        <p class="mt-1 text-[13px] text-muted">Unduh data sebagai Excel/PDF, atau import data lewat template Excel. Isi kartu
            mengikuti akses modul yang kamu punya.</p>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($catalog as $key => $entry)
            <div class="rounded-wsm border border-line bg-white p-4.5">
                <div class="flex items-start justify-between gap-2">
                    <strong class="text-sm"><span
                            class="mr-1.5 inline-block w-4 text-center">{{ $entry['icon'] }}</span>{{ $entry['label'] }}</strong>
                    @if (empty($entry['implemented_exports']) && !$entry['import_implemented'])
                        <span
                            class="flex-none rounded-full bg-[#f2f0eb] px-2.5 py-1 text-[10px] font-extrabold text-[#5e5951]">Segera
                            Hadir</span>
                    @endif
                </div>
                <p class="mt-1 text-xs text-muted">{{ $entry['desc'] }}</p>

                <div class="mt-3 flex flex-wrap items-center gap-1.5">
                    @foreach ($entry['exports'] as $format)
                        <span
                            class="rounded-full px-2.5 py-1 text-[10px] font-extrabold {{ in_array($format, $entry['implemented_exports'], true) ? 'bg-[#e2e9ff] text-[#2a3a7a]' : 'bg-[#f2f0eb] text-[#a8a296]' }}">
                            Export {{ strtoupper($format) }}
                        </span>
                    @endforeach
                    @if ($entry['import'])
                        <span
                            class="rounded-full px-2.5 py-1 text-[10px] font-extrabold {{ $entry['import_implemented'] ? 'bg-[#dff3ee] text-[#1f6b57]' : 'bg-[#f2f0eb] text-[#a8a296]' }}">
                            Import
                        </span>
                    @endif
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($entry['exports'] as $format)
                        @if (in_array($format, $entry['implemented_exports'], true))
                            <a href="{{ route('dashboard.export-import.preview', ['key' => $key, 'format' => $format]) }}"
                                class="rounded-2xl bg-ink px-3.5 py-2 text-xs font-extrabold text-white">
                                Export {{ strtoupper($format) }}
                            </a>
                        @else
                            <span
                                class="cursor-not-allowed rounded-2xl bg-[#f2f0eb] px-3.5 py-2 text-xs font-extrabold text-[#a8a296]">
                                {{ strtoupper($format) }} — Segera Hadir
                            </span>
                        @endif
                    @endforeach
                    @if ($entry['import'])
                        @if ($entry['import_implemented'])
                            <a href="{{ route('dashboard.export-import.import.show', ['key' => $key]) }}"
                                class="rounded-2xl border border-line px-3.5 py-2 text-xs font-extrabold text-[#5e5951] hover:bg-[#f2f0eb]">
                                Import
                            </a>
                        @else
                            <span
                                class="cursor-not-allowed rounded-2xl bg-[#f2f0eb] px-3.5 py-2 text-xs font-extrabold text-[#a8a296]">
                                Import — Segera Hadir
                            </span>
                        @endif
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endsection
