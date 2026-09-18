{{--
    resources/views/pdf/attendance-recap.blade.php
    -----------------------------------------------------------------
    Batch 2 — Rekap Absensi PDF PER-KARYAWAN. Dipanggil dari
    ExportController::downloadAttendanceRecapPdf(). Headings & rows
    SAMA PERSIS dengan App\Exports\AttendanceRecapExport (dipakai
    bareng, bukan query terpisah) — bedanya cuma format keluarannya
    (PDF 1 karyawan vs Excel bisa semua karyawan sekaligus).
    -----------------------------------------------------------------
--}}
@extends('pdf.layout')

@section('title', 'Rekap Absensi — ' . $employee->name)
@section('meta', 'Periode: ' . $periodLabel)

@section('content')
    <p style="margin:0 0 12px;color:#6b6459;">
        Karyawan: <strong style="color:#2a2620;">{{ $employee->name }}</strong>
    </p>

    <table class="data">
        <thead>
            <tr>
                @foreach ($headings as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headings) }}">Tidak ada data absensi untuk periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
