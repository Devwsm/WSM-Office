{{--
    dashboard/export-import/import-preview.blade.php
    -----------------------------------------------------------------
    Batch 3 (Work Tracker) + Batch 4 (Manajemen Karyawan) — halaman
    preview hasil parsing file upload, SEBELUM data beneran masuk
    database. Dua tabel terpisah: baris VALID (siap diimport, klik
    tombol "Konfirmasi Import" di bawah) dan baris ERROR (gak ikut
    masuk, pesan errornya ditampilkan per baris biar user tau apa yang
    perlu diperbaiki tanpa harus nebak-nebak).

    Tabel "Baris Valid" digeneralisir lewat $previewColumns (dari
    BaseImport::previewColumns() turunannya) — jadi 1 file blade ini
    dipakai SEMUA modul import, gak perlu bikin blade baru tiap nambah
    modul (Work Tracker & Manajemen Karyawan field-nya beda total,
    lihat App\Imports\WorkItemImport / EmployeeImport).

    Data dari controller: $title, $key, $token (buat form Konfirmasi —
    commit() ambil lagi hasil staging pakai token yang SAMA, BUKAN baca
    ulang file), $headings (kolom template, dipakai buat tabel error —
    baris error nampilin data MENTAH hasil upload, belum dipetakan),
    $previewColumns (array{label,key,type?,fallback?} — kolom tabel
    "Baris Valid", key-nya ke $entry['data'] hasil mapRow(), BUKAN ke
    $headings), $valid (array{row,data}), $invalid
    (array{row,data,errors}), $backUrl (balik ke halaman upload buat
    coba lagi), $commitUrl.
    -----------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Preview Import ' . $title, 'navActive' => 'export-import'])

@section('content')
    <div class="mb-4">
        <a href="{{ $backUrl }}" class="text-xs font-extrabold text-muted hover:text-ink">&larr; Upload Ulang</a>
        <h2 class="mt-1 text-[28px] font-black leading-[0.98] tracking-tight">Preview Import — {{ $title }}</h2>
        <p class="mt-1 text-[13px] text-muted">Cek dulu di bawah sebelum konfirmasi — baris error TIDAK akan ikut masuk
            database.</p>
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
        <span class="rounded-full bg-[#dff3ee] px-2.5 py-1 text-[10px] font-extrabold text-[#1f6b57]">
            {{ count($valid) }} baris valid
        </span>
        <span class="rounded-full bg-[#fbe2df] px-2.5 py-1 text-[10px] font-extrabold text-[#8a2f24]">
            {{ count($invalid) }} baris error
        </span>
    </div>

    <div class="mb-2 flex items-center justify-between gap-3">
        <strong class="text-sm">Baris Valid — siap diimport</strong>
    </div>
    <div class="mb-6 overflow-x-auto rounded-wsm border border-line bg-white">
        <table class="w-full text-left text-xs">
            <thead>
                <tr class="border-b border-line bg-[#f4f1ea]">
                    <th class="whitespace-nowrap px-3.5 py-2.5 font-extrabold">Baris</th>
                    @foreach ($previewColumns as $col)
                        <th class="whitespace-nowrap px-3.5 py-2.5 font-extrabold">{{ $col['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($valid as $entry)
                    <tr class="border-b border-line last:border-0">
                        <td class="whitespace-nowrap px-3.5 py-2.5 text-muted">{{ $entry['row'] }}</td>
                        @foreach ($previewColumns as $col)
                            <td class="whitespace-nowrap px-3.5 py-2.5">
                                @php $value = $entry['data'][$col['key']] ?? null; @endphp
                                @if ($value === null || $value === '')
                                    {{ $col['fallback'] ?? '-' }}
                                @elseif (($col['type'] ?? null) === 'date')
                                    {{ \Illuminate\Support\Carbon::parse($value)->format('d/m/Y') }}
                                @else
                                    {{ $value }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($previewColumns) + 1 }}" class="px-3.5 py-6 text-center text-muted">
                            Tidak ada baris valid di file ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if (count($invalid) > 0)
        <strong class="text-sm">Baris Error — tidak ikut masuk</strong>
        <div class="mt-2 overflow-x-auto rounded-wsm border border-line bg-white">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-line bg-[#f4f1ea]">
                        <th class="whitespace-nowrap px-3.5 py-2.5 font-extrabold">Baris</th>
                        @foreach ($headings as $heading)
                            <th class="whitespace-nowrap px-3.5 py-2.5 font-extrabold">{{ ucfirst($heading) }}</th>
                        @endforeach
                        <th class="px-3.5 py-2.5 font-extrabold">Error</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invalid as $entry)
                        <tr class="border-b border-line last:border-0">
                            <td class="whitespace-nowrap px-3.5 py-2.5 text-muted">{{ $entry['row'] }}</td>
                            @foreach ($headings as $heading)
                                <td class="whitespace-nowrap px-3.5 py-2.5">
                                    @if ($heading === 'password')
                                        {{-- Jangan tampilin password mentah di layar biarpun baris ini error karena alasan lain (mis. role salah ketik) — cukup kasih tau kekisi atau kosong. --}}
                                        {{ ($entry['data']['password'] ?? '') !== '' ? '(diisi)' : '-' }}
                                    @else
                                        {{ $entry['data'][$heading] ?? '-' }}
                                    @endif
                                </td>
                            @endforeach
                            <td class="px-3.5 py-2.5 text-[#8a2f24]">
                                @foreach ($entry['errors'] as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <form method="POST" action="{{ $commitUrl }}" class="mt-6">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        @if (count($valid) > 0)
            <button type="submit" class="rounded-2xl bg-ink px-4 py-2.5 text-xs font-extrabold text-white">
                Konfirmasi Import {{ count($valid) }} Baris
            </button>
            <p class="mt-2 text-[11px] text-muted">Baris error di atas dilewati — perbaiki filenya lalu upload ulang kalau
                mau baris itu ikut masuk juga.</p>
        @else
            <p class="text-xs text-muted">Tidak ada baris valid buat diimport — perbaiki filenya lalu upload ulang.</p>
        @endif
    </form>
@endsection
