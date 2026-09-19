{{--
    dashboard/export-import/picker.blade.php
    -----------------------------------------------------------------
    Batch 2 — dipakai KHUSUS 2 entri catalog yang PDF-nya 1 DOKUMEN
    PER RECORD, bukan 1 file berisi tabel banyak baris: Payroll (slip
    gaji per-karyawan-per-periode) & Meetings/MoM (notulen per-rapat).
    Beda dari preview.blade.php (yang punya 1 tombol "Download" buat
    seluruh hasil filter), di sini tiap BARIS punya tombol download-nya
    sendiri — user pilih 1 record dulu, langsung download PDF-nya
    (gak ada halaman preview antara lagi, karena PDF-nya sendiri
    SUDAH berupa dokumen siap lihat/print, beda kasus sama tabel Excel
    yang perlu dicek datanya dulu sebelum di-generate jadi file).

    Data dari controller: $title, $backUrl, $filters (opsional, lewat
    partial yang sama), $columns (label kolom tabel), $rows — array
    of ['cells' => [...], 'downloadUrl' => '...', 'downloadLabel' =>
    '...'], $emptyMessage.

    Tombol download per-baris selalu kuning/emas (warna PDF) — halaman
    ini KHUSUS PDF (lihat catatan di atas), gak pernah dipakai buat
    Excel.
    -----------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => $title, 'navActive' => 'export-import'])

@section('content')
    <div class="mb-4">
        <a href="{{ $backUrl }}" class="text-xs font-extrabold text-muted hover:text-ink">&larr; Export &amp; Import</a>
        <h2 class="mt-1 text-[28px] font-black leading-[0.98] tracking-tight">{{ $title }}</h2>
        <p class="mt-1 text-[13px] text-muted">Pilih salah satu di bawah, PDF-nya langsung terdownload.</p>
    </div>

    @include('dashboard.export-import._filters-form', ['filters' => $filters])

    <div class="overflow-x-auto rounded-wsm border border-line bg-white">
        <table class="w-full text-left text-xs">
            <thead>
                <tr class="border-b border-line bg-[#f4f1ea]">
                    @foreach ($columns as $column)
                        <th class="whitespace-nowrap px-3.5 py-2.5 font-extrabold">{{ $column }}</th>
                    @endforeach
                    <th class="px-3.5 py-2.5"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr class="border-b border-line last:border-0">
                        @foreach ($row['cells'] as $cell)
                            <td class="whitespace-nowrap px-3.5 py-2.5">{{ $cell }}</td>
                        @endforeach
                        <td class="whitespace-nowrap px-3.5 py-2.5 text-right">
                            <a href="{{ $row['downloadUrl'] }}"
                                class="rounded-2xl bg-brand-yellow px-3 py-1.5 text-[11px] font-extrabold text-[#3a2e00] transition hover:brightness-110">
                                {{ $row['downloadLabel'] }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) + 1 }}" class="px-3.5 py-6 text-center text-muted">
                            {{ $emptyMessage }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
