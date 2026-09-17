{{--
    dashboard/export-import/index.blade.php
    -----------------------------------------------------------------
    Batch 0 (fondasi Export & Import) — kartu per modul dari
    ExportCatalog::visibleFor(), pola grid-nya nyontek
    dashboard/index.blade.php biar konsisten visual sama landing
    "Dashboard" biasa.

    'implemented' => false (lihat ExportCatalog) bikin kartu kelihatan
    tapi tombolnya nonaktif + badge "Segera Hadir" — DIHAPUS
    kondisinya satu-satu pas modul terkait beneran dikerjakan di
    Batch 1/2/3, jangan dihapus borongan sebelum semua modul selesai.
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
                    @unless ($entry['implemented'])
                        <span
                            class="flex-none rounded-full bg-[#f2f0eb] px-2.5 py-1 text-[10px] font-extrabold text-[#5e5951]">Segera
                            Hadir</span>
                    @endunless
                </div>
                <p class="mt-1 text-xs text-muted">{{ $entry['desc'] }}</p>

                <div class="mt-3 flex flex-wrap items-center gap-1.5">
                    @foreach ($entry['exports'] as $format)
                        <span class="rounded-full bg-[#e2e9ff] px-2.5 py-1 text-[10px] font-extrabold text-[#2a3a7a]">
                            Export {{ strtoupper($format) }}
                        </span>
                    @endforeach
                    @if ($entry['import'])
                        <span class="rounded-full bg-[#dff3ee] px-2.5 py-1 text-[10px] font-extrabold text-[#1f6b57]">
                            Import
                        </span>
                    @endif
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    @if ($entry['implemented'])
                        @foreach ($entry['exports'] as $format)
                            <a href="{{ route($entry['export_route'], ['format' => $format]) }}"
                                class="rounded-2xl bg-ink px-3.5 py-2 text-xs font-extrabold text-white">
                                Export {{ strtoupper($format) }}
                            </a>
                        @endforeach
                        @if ($entry['import'])
                            <a href="{{ route($entry['import_route']) }}"
                                class="rounded-2xl border border-line px-3.5 py-2 text-xs font-extrabold text-[#5e5951] hover:bg-[#f2f0eb]">
                                Import
                            </a>
                        @endif
                    @else
                        <span
                            class="cursor-not-allowed rounded-2xl bg-[#f2f0eb] px-3.5 py-2 text-xs font-extrabold text-[#a8a296]">
                            Belum tersedia
                        </span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endsection
