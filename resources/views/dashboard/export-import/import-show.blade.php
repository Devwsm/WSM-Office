{{--
    dashboard/export-import/import-show.blade.php
    -----------------------------------------------------------------
    Batch 3 — halaman upload buat SATU modul import ($key). Alurnya:
    download template kosong dulu (tombol atas) -> isi -> upload lewat
    form di bawah (POST multipart ke $previewUrl) -> lanjut ke
    import-preview.blade.php (baris valid vs error, BELUM masuk
    database di tahap ini).

    Data dari controller: $title, $key, $headings (nama kolom template,
    urutan buat template Excel-nya), $fieldNotes (dari
    BaseImport::fieldNotes() turunannya — array<string, array{required:
    bool, note?: string}> keyed per heading, ditambah Batch 4 biar user
    tau SEBELUM ngisi Excel-nya kolom mana wajib/boleh kosong & apa
    yang kejadian kalau dikosongin, mis. `password` -> fallback
    "password" kalau kosong), $backUrl, $templateUrl, $previewUrl.
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
        <p class="mt-1 text-xs text-muted">Jangan ubah nama kolom di baris pertama — baris contoh (huruf miring) boleh
            dihapus. Kolom yang dipakai:</p>

        <ul class="mt-2 space-y-1.5 text-xs">
            @foreach ($headings as $heading)
                @php $noteInfo = $fieldNotes[$heading] ?? ['required' => false]; @endphp
                <li class="flex flex-wrap items-baseline gap-x-1.5">
                    <code class="rounded bg-[#f2f0eb] px-1.5 py-0.5 font-mono font-bold">{{ $heading }}</code>
                    @if ($noteInfo['required'] ?? false)
                        <span
                            class="rounded-full bg-[#fbe2df] px-2 py-0.5 text-[10px] font-extrabold text-[#8a2f24]">Wajib</span>
                    @else
                        <span class="rounded-full bg-[#dff3ee] px-2 py-0.5 text-[10px] font-extrabold text-[#1f6b57]">Boleh
                            kosong</span>
                    @endif
                    @if (!empty($noteInfo['note']))
                        <span class="text-muted">— {{ $noteInfo['note'] }}</span>
                    @endif
                </li>
            @endforeach
        </ul>

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
            <button type="submit"
                class="rounded-2xl border border-line bg-white px-4 py-2.5 text-xs font-extrabold text-ink transition hover:bg-cream">
                Lihat Preview
            </button>
        </form>
    </div>
@endsection
