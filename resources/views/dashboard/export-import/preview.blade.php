{{--
    dashboard/export-import/preview.blade.php
    -----------------------------------------------------------------
    Batch 1/2 — SATU view generik dipakai preview modul export yang
    bentuknya TABEL (Excel semua modul Batch 1 + PDF Rekap Absensi
    per-karyawan Batch 2), isinya cuma dari controller: $title,
    $headings, $rows (array polos hasil map(), BUKAN Eloquent
    Collection), $filters (form filter opsional per modul, lewat
    partial _filters-form.blade.php), $downloadUrl, $downloadLabel
    ("Download Excel"/"Download PDF"), $backUrl, $requiresSelection
    (opsional, default false — Batch 2: Rekap Absensi PDF WAJIB pilih
    1 karyawan dulu, beda dari versi Excel-nya yang boleh semua
    karyawan sekaligus; selagi belum dipilih, tombol download
    disembunyikan & $rows dikirim kosong dari controller).

    Warna tombol Download ikut format ($downloadLabel) — hijau buat
    Excel, merah buat PDF — sama 2 warna yang dipakai buat badge
    "Export EXCEL"/"Export PDF" di index.blade.php, biar konsisten.

    Modul yang PDF-nya berupa 1 DOKUMEN PER RECORD (bukan tabel
    banyak baris) — Payroll (slip gaji) & Meetings (notulen) — TIDAK
    lewat view ini, tapi lewat dashboard/export-import/picker.blade.php
    (pilih 1 record dulu, baru download PDF-nya). Lihat catatan di
    App\Http\Controllers\Dashboard\ExportImport\ExportController.
    -----------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => $title, 'navActive' => 'export-import'])

@php
    $isPdfDownload = str_contains($downloadLabel ?? 'Download Excel', 'PDF');
@endphp

@section('content')
    <div class="mb-4 flex items-center justify-between gap-3">
        <div>
            <a href="{{ $backUrl }}" class="text-xs font-extrabold text-muted hover:text-ink">&larr; Export &amp;
                Import</a>
            <h2 class="mt-1 text-[28px] font-black leading-[0.98] tracking-tight">{{ $title }}</h2>
            <p class="mt-1 text-[13px] text-muted">Preview di bawah — cek dulu datanya sebelum download.</p>
        </div>
        @unless ($requiresSelection ?? false)
            <a href="{{ $downloadUrl }}"
                class="flex-none rounded-2xl px-4 py-2.5 text-xs font-extrabold text-white transition hover:brightness-110 {{ $isPdfDownload ? 'bg-brand-red' : 'bg-brand-green' }}">
                {{ $downloadLabel ?? 'Download Excel' }}
            </a>
        @endunless
    </div>

    @include('dashboard.export-import._filters-form', ['filters' => $filters])

    @if ($requiresSelection ?? false)
        <div class="mb-4 rounded-wsm border border-line bg-[#faf8f3] p-3.5 text-xs text-muted">
            Pilih karyawan dulu di filter di atas buat lihat &amp; download PDF rekap absensinya.
        </div>
    @endif

    <div class="overflow-x-auto rounded-wsm border border-line bg-white">
        <table class="w-full text-left text-xs">
            <thead>
                <tr class="border-b border-line bg-[#f4f1ea]">
                    @foreach ($headings as $heading)
                        <th class="whitespace-nowrap px-3.5 py-2.5 font-extrabold">{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr class="border-b border-line last:border-0">
                        @foreach ($row as $cell)
                            <td class="whitespace-nowrap px-3.5 py-2.5">{{ $cell }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($headings) }}" class="px-3.5 py-6 text-center text-muted">
                            Tidak ada data untuk filter ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="mt-3 text-[11px] text-muted">Menampilkan {{ count($rows) }} baris.</p>
@endsection
