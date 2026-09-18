{{--
    dashboard/export-import/import-show.blade.php
    -----------------------------------------------------------------
    Batch 3 — halaman upload buat SATU modul import ($key). Alurnya:
    download template kosong dulu (tombol atas) -> isi -> upload lewat
    form di bawah (POST multipart ke $previewUrl) -> lanjut ke
    import-preview.blade.php (baris valid vs error, BELUM masuk
    database di tahap ini).

    Data dari controller: $title, $key, $headings (nama kolom template,
    buat ditampilkan sebagai panduan), $backUrl, $templateUrl,
    $previewUrl.
    -----------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Import ' . $title, 'navActive' => 'export-import'])

@section('content')
    <div class="mb-4">
        <a href="{{ $backUrl }}" class="text-xs font-extrabold text-muted hover:text-ink">&larr; Export &amp; Import</a>
        <h2 class="mt-1 text-[28px] font-black leading-[0.98] tracking-tight">Import — {{ $title }}</h2>
        <p class="mt-1 text-[13px] text-muted">Download template Excel kosong, isi datanya, lalu upload lagi di sini.
            Kamu tetap bisa cek dulu mana baris yang valid/error sebelum data beneran masuk.</p>
    </div>

    <div class="mb-4 rounded-wsm border border-line bg-white p-4.5">
        <strong class="text-sm">1. Download Template</strong>
        <p class="mt-1 text-xs text-muted">Kolom yang dipakai: {{ implode(', ', $headings) }}. Jangan ubah nama kolom di
            baris pertama — baris contoh (huruf miring) boleh dihapus.</p>
        <a href="{{ $templateUrl }}"
            class="mt-3 inline-block rounded-2xl border border-line px-3.5 py-2 text-xs font-extrabold text-[#5e5951] hover:bg-[#f2f0eb]">
            Download Template Excel
        </a>
    </div>

    <div class="rounded-wsm border border-line bg-white p-4.5">
        <strong class="text-sm">2. Upload File Terisi</strong>
        <p class="mt-1 text-xs text-muted">Format .xlsx, .xls, atau .csv, maksimal 5 MB.</p>

        <form method="POST" action="{{ $previewUrl }}" enctype="multipart/form-data"
            class="mt-3 flex flex-wrap items-center gap-2">
            @csrf
            <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                class="rounded-xl border border-line px-3 py-2 text-sm">
            <button type="submit" class="rounded-2xl bg-ink px-4 py-2.5 text-xs font-extrabold text-white">
                Lihat Preview
            </button>
        </form>
    </div>
@endsection
