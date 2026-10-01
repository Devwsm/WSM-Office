{{--
    dashboard/work/projects/sync.blade.php
    ---------------------------------------------------------------------
    Item 6 selisih prototype v32 — "Sinkron Sheet" per project: upload file
    tracker (Excel/CSV dari Google Sheet, atau hasil "Download Excel" yang
    sudah diedit), lihat preview perubahan, baru terapkan. Logic-nya di
    App\Support\ProjectSheet\SheetSync; controller ProjectSheetController.
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Sinkron Sheet — ' . $project->name, 'navActive' => 'modules'])

@section('content')
    <div class="mb-5">
        <a href="{{ route('dashboard.work.projects.index') }}" class="text-[11px] font-extrabold text-muted">← Projects</a>
        <h2 class="mt-2 flex items-center gap-2 text-3xl font-black leading-[0.98] tracking-tight sm:text-[34px]">
            <span class="h-3 w-3 flex-none rounded-full" style="background:{{ $project->color }}"></span>
            <span class="min-w-0 wrap-break-word">Sinkron Sheet — {{ $project->name }}</span>
        </h2>
        <p class="mt-1 text-[13px] text-muted">Samakan task project ini dengan tracker di Google Sheet. Project ini punya
            {{ $itemCount }} task di WSM. Belum ada yang tersimpan sebelum kamu konfirmasi di halaman preview.</p>
    </div>

    <div class="mb-4 rounded-wsm border border-line bg-white p-4.5">
        <strong class="text-sm">1. Siapkan file</strong>
        <ol class="mt-2 list-decimal space-y-1.5 pl-5 text-xs text-[#5e5951]">
            <li><strong>Dari Google Sheet ke WSM:</strong> di Google Sheet pilih <em>File → Download → Microsoft Excel
                    (.xlsx)</em> (atau CSV) untuk tab tracker project ini.</li>
            <li><strong>Dari WSM ke Google Sheet:</strong> klik <em>Download Excel</em> di kartu project, edit di Google
                Sheet, lalu unggah hasilnya di sini. Kolom <code class="rounded bg-[#f2f0eb] px-1 font-mono font-bold">ID
                    WSM</code> di paling kanan jangan dihapus — itu yang mencegah task jadi dobel.</li>
        </ol>
        <a href="{{ route('dashboard.work.tracker.projects.excel', $project) }}"
            class="mt-3 inline-block rounded-2xl border border-line px-3.5 py-2 text-xs font-extrabold text-[#5e5951] hover:bg-[#f2f0eb]">Download
            Excel project ini</a>
    </div>

    <div class="mb-4 rounded-wsm border border-line bg-white p-4.5">
        <strong class="text-sm">Aturan sinkron</strong>
        <ul class="mt-2 list-disc space-y-1.5 pl-5 text-xs text-[#5e5951]">
            <li>Baris yang punya <strong>ID WSM</strong> memperbarui task itu. Baris tanpa ID dicocokkan lewat
                <strong>section + judul</strong>; kalau tidak ketemu, dibuat sebagai <strong>task baru</strong>.
            </li>
            <li><strong>Tidak ada yang dihapus.</strong> Task di WSM yang tidak ada di file dibiarkan apa adanya.</li>
            <li><strong>Sel kosong tidak mengosongkan</strong> data di WSM — hanya sel yang terisi yang menimpa.</li>
            <li>PIC dicocokkan ke nama karyawan (maksimal 3). Awalan <em>Mas/Kak/Om</em> dan akhiran <em>WS Team</em>
                diabaikan; nama yang tidak dikenal (mis. <em>ALL TEAM</em>) disimpan sebagai teks saja.</li>
            <li>Kolom LINK hanya menyimpan URL sungguhan. Kolom FOCUS dihitung otomatis dari tanggal, jadi diabaikan.</li>
        </ul>
    </div>

    <div class="rounded-wsm border border-line bg-white p-4.5">
        <strong class="text-sm">2. Upload file tracker</strong>
        <p class="mt-1 text-xs text-muted">Format .xlsx, .xls, atau .csv, maksimal 5 MB.</p>

        <form method="POST" action="{{ route('dashboard.work.tracker.projects.sync.preview', $project) }}"
            enctype="multipart/form-data" class="mt-3 flex flex-wrap items-center gap-2">
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
