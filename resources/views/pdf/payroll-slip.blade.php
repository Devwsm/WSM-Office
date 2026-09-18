{{--
    resources/views/pdf/payroll-slip.blade.php
    -----------------------------------------------------------------
    Batch 2 — Slip Gaji PDF 1 karyawan 1 periode. Breakdown sama
    persis dengan dashboard/payroll/show.blade.php (Fase 12) — biar
    angka yang muncul di layar & di PDF yang didownload selalu cocok,
    cuma bedanya di sini gak ada form penyesuaian/tombol aksi (murni
    dokumen buat diprint/dikirim ke karyawan).

    Slip untuk record berstatus `draft` SENGAJA dikasih watermark
    teks "DRAFT" — angkanya masih bisa berubah sampai difinalisasi,
    jangan sampai karyawan nerima slip draft dikira final.
    -----------------------------------------------------------------
--}}
@extends('pdf.layout')

@section('title', 'Slip Gaji — ' . $payroll->user->name)
@section('meta', 'Periode: ' . $payroll->periodLabel())

@section('content')
    @if ($payroll->status === 'draft')
        <p
            style="margin:0 0 12px;padding:6px 10px;background:#fbeceb;color:#a83d35;font-weight:bold;font-size:10px;border-radius:4px;">
            DRAFT — angka belum final, masih bisa berubah sampai payroll ini difinalisasi.
        </p>
    @endif

    <p style="margin:0 0 12px;color:#6b6459;">
        Karyawan: <strong style="color:#2a2620;">{{ $payroll->user->name }}</strong>
        &nbsp;·&nbsp; Status: <strong style="color:#2a2620;">{{ $payroll->statusLabel() }}</strong>
    </p>

    <table class="data">
        <tbody>
            <tr>
                <td style="width:65%;">Gaji Pokok</td>
                <td>{{ \App\Models\PayrollRecord::formatRupiah($payroll->base_salary) }}</td>
            </tr>
            <tr>
                <td>
                    Lembur
                    <span style="display:block;color:#6b6459;font-size:9px;">
                        {{ $overtimeCount }} pengajuan disetujui ×
                        {{ \App\Models\PayrollRecord::formatRupiah($payroll->user->flat_overtime_rate) }}
                    </span>
                </td>
                <td>+{{ \App\Models\PayrollRecord::formatRupiah($payroll->overtime_amount) }}</td>
            </tr>
            <tr>
                <td>
                    Potongan Kurang Jam
                    <span style="display:block;color:#6b6459;font-size:9px;">
                        {{ $shortage['blocks'] }} blok (sisa {{ $shortage['remainder_minutes'] }} menit dibawa
                        bulan depan) × {{ \App\Models\PayrollRecord::formatRupiah($shortageRate) }}
                    </span>
                </td>
                <td>-{{ \App\Models\PayrollRecord::formatRupiah($payroll->shortage_deduction) }}</td>
            </tr>
            <tr>
                <td>Penyesuaian Lain</td>
                <td>{{ \App\Models\PayrollRecord::formatRupiah($payroll->other_adjustment) }}</td>
            </tr>
            <tr>
                <td><strong>Total</strong></td>
                <td><strong>{{ \App\Models\PayrollRecord::formatRupiah($payroll->total) }}</strong></td>
            </tr>
        </tbody>
    </table>

    @if ($payroll->notes)
        <p style="margin-top:14px;font-size:10px;">
            <strong>Catatan:</strong><br>
            <span style="white-space:pre-line;">{{ $payroll->notes }}</span>
        </p>
    @endif
@endsection
