{{--
    resources/views/pdf/budget-report.blade.php
    -----------------------------------------------------------------
    Laporan Anggaran Project (PDF, A4 landscape) — padanan "Print Budget"
    prototype v23. Dipanggil dari BudgetController::pdf(). Angkanya dari
    App\Support\BudgetReport, sama persis dengan halaman Project Budgeting.

    Layout: ringkasan → grafik Budget vs Actual (dua batang tipis
    bertumpuk, bukan overlay — dompdf tidak andal untuk posisi absolut)
    → tabel per project. Style inline/<style> biasa, bukan Tailwind (lihat
    catatan di pdf/layout.blade.php).
    -----------------------------------------------------------------
--}}
@extends('pdf.layout')

@php
    $rp = fn($value) => \App\Models\PayrollRecord::formatRupiah($value);
    $pct = fn($value) => $value === null ? '-' : $value . '%';
    $red = '#a83d35';
    $green = '#3d7a4d';
    $barWidth = 230; // px, lebar maksimum batang
@endphp

@section('title', 'Laporan Anggaran Project')
@section('meta', 'Cakupan: ' . $scopeLabel)

@section('content')
    <table class="data" style="margin-bottom:16px;">
        <tr>
            <th style="width:20%;">Project Budget</th>
            <th style="width:20%;">Budget Allocation</th>
            <th style="width:20%;">Actual</th>
            <th style="width:20%;">Remaining</th>
            <th style="width:20%;">Utilization</th>
        </tr>
        <tr>
            <td style="font-size:13px;font-weight:bold;">{{ ($projectBudget ?? 0) > 0 ? $rp($projectBudget) : '-' }}</td>
            <td style="font-size:13px;font-weight:bold;">{{ $rp($totals['budget']) }}</td>
            <td style="font-size:13px;font-weight:bold;">{{ $rp($totals['actual']) }}</td>
            <td style="font-size:13px;font-weight:bold;color:{{ $totals['remaining'] < 0 ? $red : $green }};">
                {{ $rp($totals['remaining']) }}
                @if ($totals['remaining'] < 0)
                    <span style="font-size:9px;">(melebihi budget)</span>
                @endif
            </td>
            <td style="font-size:13px;font-weight:bold;">{{ $pct($totals['utilization']) }}</td>
        </tr>
    </table>

    <h2 style="font-size:12px;margin:0 0 6px;">Budget vs Actual — {{ $groupLabel }}</h2>
    <table style="width:100%;border-collapse:collapse;margin-bottom:6px;">
        @foreach ($chart as $bar)
            <tr style="page-break-inside:avoid;">
                <td style="width:22%;padding:4px 6px 4px 0;font-weight:bold;font-size:10px;vertical-align:middle;">
                    {{ $bar['label'] }}</td>
                <td style="width:{{ $barWidth + 10 }}px;padding:4px 6px;vertical-align:middle;">
                    <div
                        style="height:6px;width:{{ max($bar['budget'] > 0 ? 2 : 0, (int) round(($barWidth * $bar['plan_pct']) / 100)) }}px;background-color:#b9c9f5;margin-bottom:2px;">
                    </div>
                    <div
                        style="height:6px;width:{{ max($bar['actual'] > 0 ? 2 : 0, (int) round(($barWidth * $bar['actual_pct']) / 100)) }}px;background-color:{{ $bar['over'] ? '#d4574c' : '#3558f4' }};">
                    </div>
                </td>
                <td style="padding:4px 0 4px 6px;font-size:10px;vertical-align:middle;">
                    <strong>{{ $rp($bar['actual']) }}</strong> / {{ $rp($bar['budget']) }}
                    ({{ $pct($bar['utilization']) }})
                    @if ($bar['over'])
                        <strong style="color:{{ $red }};">Melebihi budget</strong>
                    @endif
                </td>
            </tr>
        @endforeach
    </table>
    <p style="font-size:8px;color:#6b6459;margin:0 0 16px;">
        Batang atas = Budget (biru muda), batang bawah = Actual (biru; merah jika melebihi budget).
    </p>

    @foreach ($entriesByProject as $projectGroup)
        <h2 style="font-size:12px;margin:0 0 4px;">
            {{ $projectGroup['project'] }}
            @if ($projectGroup['remaining'] < 0)
                <span style="font-size:9px;color:{{ $red }};">— Melebihi budget</span>
            @endif
        </h2>
        <table class="data" style="margin-bottom:14px;">
            <thead>
                <tr>
                    <th style="width:11%;">Kategori</th>
                    <th style="width:19%;">Item</th>
                    <th style="width:12%;">Lagu</th>
                    <th style="width:11%;text-align:right;">Budget</th>
                    <th style="width:11%;text-align:right;">Actual</th>
                    <th style="width:11%;text-align:right;">Variance</th>
                    <th style="width:6%;">Bukti</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($projectGroup['rows'] as $row)
                    @php $variance = $row->variance(); @endphp
                    <tr style="page-break-inside:avoid;">
                        <td>{{ $row->category }}</td>
                        <td><strong>{{ $row->item }}</strong></td>
                        <td>{{ $row->song_title ?: '-' }}</td>
                        <td style="text-align:right;">{{ $rp($row->budget) }}</td>
                        <td style="text-align:right;">{{ $rp($row->actual) }}</td>
                        <td style="text-align:right;font-weight:bold;color:{{ $variance < 0 ? $red : $green }};">
                            {{ $rp($variance) }}</td>
                        <td>
                            @if ($row->proof_link)
                                <a href="{{ $row->proof_link }}" style="color:#2a2620;">Ada</a>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $row->note ?: '-' }}</td>
                    </tr>
                @endforeach
                <tr style="background-color:#f4f1ea;font-weight:bold;">
                    <td colspan="3">Subtotal ({{ $pct($projectGroup['utilization']) }} terpakai)</td>
                    <td style="text-align:right;">{{ $rp($projectGroup['budget']) }}</td>
                    <td style="text-align:right;">{{ $rp($projectGroup['actual']) }}</td>
                    <td style="text-align:right;color:{{ $projectGroup['remaining'] < 0 ? $red : $green }};">
                        {{ $rp($projectGroup['remaining']) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>
    @endforeach
@endsection
