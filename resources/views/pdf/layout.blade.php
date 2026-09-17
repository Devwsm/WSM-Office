{{--
    resources/views/pdf/layout.blade.php
    -----------------------------------------------------------------
    Batch 0 (fondasi Export & Import) — kop surat/letterhead dasar,
    dipakai SEMUA export PDF (Batch 2: slip gaji Payroll, rekap
    Absensi per-karyawan, notulen Meetings/MoM). Butuh package
    `barryvdh/laravel-dompdf` (belum terpasang — lihat README bagian
    Export & Import).

    CARA PAKAI (Batch 2 dst.): @extends('pdf.layout') dari view PDF
    spesifik, isi @section('title', '...') dan @section('content').
    JANGAN pakai Tailwind/class dari resources/css/app.css di sini —
    dompdf cuma ngerti CSS inline/<style> biasa, gak jalanin build
    Vite. Warna brand (#5E0006) & style di bawah SENGAJA ditulis
    manual, bukan @vite(...), karena PDF di-render di server pakai
    dompdf, bukan di browser.
    -----------------------------------------------------------------
--}}
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Dokumen')</title>
    <style>
        @page {
            margin: 28px 32px;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #2a2620;
        }

        .header {
            display: table;
            width: 100%;
            border-bottom: 2px solid #5E0006;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }

        .header .brand {
            display: table-cell;
            vertical-align: bottom;
        }

        .header .brand strong {
            font-size: 16px;
            color: #5E0006;
        }

        .header .brand span {
            display: block;
            font-size: 9px;
            color: #6b6459;
        }

        .header .meta {
            display: table-cell;
            text-align: right;
            vertical-align: bottom;
            font-size: 9px;
            color: #6b6459;
        }

        h1.doc-title {
            font-size: 14px;
            margin: 0 0 12px;
            color: #2a2620;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
        }

        table.data th,
        table.data td {
            border: 1px solid #d8d2c4;
            padding: 5px 7px;
            text-align: left;
            font-size: 10px;
        }

        table.data th {
            background-color: #f4f1ea;
            font-weight: bold;
        }

        .footer {
            position: fixed;
            bottom: -18px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #9a9384;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="brand">
            <strong>Whisnu Santika Music</strong>
            <span>WSM Office System</span>
        </div>
        <div class="meta">
            Dicetak: {{ now()->translatedFormat('d F Y, H:i') }} WIB<br>
            @yield('meta')
        </div>
    </div>

    <h1 class="doc-title">@yield('title', 'Dokumen')</h1>

    @yield('content')

    <div class="footer">
        Dokumen ini digenerate otomatis oleh WSM Office System — {{ now()->year }}
    </div>
</body>

</html>
