{{--
    dashboard/export-import/preview.blade.php
    -----------------------------------------------------------------
    Batch 1 — SATU view generik dipakai preview SEMUA modul export
    Excel (dan nanti PDF di Batch 2), isinya cuma dari controller:
    $title, $headings, $rows (array polos hasil map(), BUKAN
    Eloquent Collection), $filters (form filter opsional per modul),
    $downloadUrl, $backUrl. Jangan bikin file preview baru per modul
    — kalau ada modul yang butuh tampilan preview beda banget,
    tambahin kondisi di sini, bukan file baru.
    -----------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => $title, 'navActive' => 'export-import'])

@section('content')
    <div class="mb-4 flex items-center justify-between gap-3">
        <div>
            <a href="{{ $backUrl }}" class="text-xs font-extrabold text-muted hover:text-ink">&larr; Export &amp;
                Import</a>
            <h2 class="mt-1 text-[28px] font-black leading-[0.98] tracking-tight">{{ $title }}</h2>
            <p class="mt-1 text-[13px] text-muted">Preview di bawah — cek dulu datanya sebelum download.</p>
        </div>
        <a href="{{ $downloadUrl }}" class="flex-none rounded-2xl bg-ink px-4 py-2.5 text-xs font-extrabold text-white">
            Download Excel
        </a>
    </div>

    @if (!empty($filters))
        <form method="GET" class="mb-4 flex flex-wrap items-end gap-2 rounded-wsm border border-line bg-white p-3.5">
            @foreach ($filters as $filter)
                <div>
                    <label class="mb-1 block text-[11px] font-extrabold text-muted">{{ $filter['label'] }}</label>
                    @if ($filter['type'] === 'month')
                        <input type="month" name="{{ $filter['name'] }}" value="{{ $filter['value'] }}"
                            class="rounded-xl border border-line px-3 py-2 text-sm">
                    @elseif ($filter['type'] === 'date')
                        <input type="date" name="{{ $filter['name'] }}" value="{{ $filter['value'] }}"
                            class="rounded-xl border border-line px-3 py-2 text-sm">
                    @elseif ($filter['type'] === 'select')
                        <select name="{{ $filter['name'] }}" class="rounded-xl border border-line px-3 py-2 text-sm">
                            <option value="">Semua</option>
                            @foreach ($filter['options'] as $optValue => $optLabel)
                                <option value="{{ $optValue }}" @selected((string) $filter['value'] === (string) $optValue)>
                                    {{ $optLabel }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                </div>
            @endforeach
            <button type="submit"
                class="rounded-2xl bg-[#f2f0eb] px-4 py-2 text-xs font-extrabold text-ink hover:bg-[#e8e5dc]">
                Terapkan Filter
            </button>
        </form>
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
