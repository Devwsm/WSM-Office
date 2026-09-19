{{--
    dashboard/export-import/index.blade.php
    -----------------------------------------------------------------
    Batch 0/1 — kartu per modul dari ExportCatalog::visibleFor(). Tiap
    FORMAT (excel/pdf) dicek independen ke $entry['implemented_exports']
    — makanya 1 kartu bisa punya tombol "Export Excel" yang aktif
    BARENGAN "Export PDF" yang masih "Segera Hadir" (mis. Rekap
    Absensi: Excel Batch 1, PDF Batch 2). Import juga dicek terpisah
    lewat 'import_implemented'.

    Warna badge & tombol dibedakan per TIPE aksi (bukan cuma
    aktif/nonaktif) — Excel hijau, PDF merah, Import putih (border) —
    Excel/PDF pakai warna aksen brand-green/brand-red yang sudah ada di
    app.css (sama yang dipakai badge-wsm-green/red di modul lain), dan
    Import sengaja putih+border (bukan warna solid) biar gak nyaingin
    intensitas Excel/PDF — sama pola .btn-wsm-white yang sudah ada,
    cuma diresize ke ukuran pill kecil di halaman ini. Badge nonaktif &
    "Segera Hadir" tetap abu-abu (`badge-wsm-gray`) di semua kondisi —
    abu-abu artinya "belum bisa dipakai", bukan salah satu dari 3 tipe
    aksi di atas.
    -----------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Export & Import', 'navActive' => 'export-import'])

@php
    // Kelas badge (pill kecil) & tombol (aksi beneran) per format
    // export — dipakai bareng di 2 tempat di bawah (badge ringkasan +
    // tombol aksi), biar warnanya konsisten dalam 1 kartu.
    $exportBadgeClass = fn(string $format) => match ($format) {
        'excel' => 'badge-wsm-green',
        'pdf' => 'badge-wsm-red',
        default => 'badge-wsm-gray',
    };
    $exportButtonClass = fn(string $format) => match ($format) {
        'excel'
            => 'rounded-2xl bg-brand-green px-3.5 py-2 text-xs font-extrabold text-white transition hover:brightness-110',
        'pdf'
            => 'rounded-2xl bg-brand-red px-3.5 py-2 text-xs font-extrabold text-white transition hover:brightness-110',
        default => 'rounded-2xl bg-ink px-3.5 py-2 text-xs font-extrabold text-white',
    };
    // Badge putih gak ada di app.css (badge-wsm-* lain semua warna
    // solid) — ditulis inline di sini, border biar kelihatan di atas
    // kartu bg-white, bukan nyatu jadi invisible.
    $importBadgeClass =
        'inline-flex items-center rounded-full border border-line bg-white px-2.5 py-1 text-[10px] font-black text-ink';
@endphp

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
                        <span class="badge-wsm-gray flex-none">Segera Hadir</span>
                    @endif
                </div>
                <p class="mt-1 text-xs text-muted">{{ $entry['desc'] }}</p>

                <div class="mt-3 flex flex-wrap items-center gap-1.5">
                    @foreach ($entry['exports'] as $format)
                        <span
                            class="{{ in_array($format, $entry['implemented_exports'], true) ? $exportBadgeClass($format) : 'badge-wsm-gray' }}">
                            Export {{ strtoupper($format) }}
                        </span>
                    @endforeach
                    @if ($entry['import'])
                        <span class="{{ $entry['import_implemented'] ? $importBadgeClass : 'badge-wsm-gray' }}">
                            Import
                        </span>
                    @endif
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($entry['exports'] as $format)
                        @if (in_array($format, $entry['implemented_exports'], true))
                            <a href="{{ route('dashboard.export-import.preview', ['key' => $key, 'format' => $format]) }}"
                                class="{{ $exportButtonClass($format) }}">
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
                                class="rounded-2xl border border-line bg-white px-3.5 py-2 text-xs font-extrabold text-ink transition hover:bg-cream">
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
