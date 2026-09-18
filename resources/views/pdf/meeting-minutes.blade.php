{{--
    resources/views/pdf/meeting-minutes.blade.php
    -----------------------------------------------------------------
    Batch 2 — Notulen Rapat (MoM) PDF, padanan
    dashboard/work/meetings/show.blade.php (Fase 9 lanjutan) tapi
    versi siap print/dikirim. Ambil dari model `Meeting` yang sama
    (attendees, persons_text, notes, decisions, actionItems) — bukan
    query terpisah.
    -----------------------------------------------------------------
--}}
@extends('pdf.layout')

@section('title', 'Notulen Rapat — ' . $meeting->agenda)
@section('meta', $meeting->date->translatedFormat('d F Y') . ($meeting->time ? ' · ' . substr($meeting->time, 0, 5) . '
    WIB' : '') . ($meeting->project ? ' · ' . $meeting->project->name : ''))

@section('content')
    <p style="margin:0 0 10px;font-size:10px;">
        <strong>Peserta:</strong><br>
        {{ $meeting->attendees->pluck('name')->implode(', ') ?: '-' }}
        @if ($meeting->persons_text)
            <br><span style="color:#6b6459;">Tambahan: {{ $meeting->persons_text }}</span>
        @endif
    </p>

    <p style="margin:0 0 10px;font-size:10px;">
        <strong>Catatan:</strong><br>
        <span style="white-space:pre-line;">{{ $meeting->notes ?: '-' }}</span>
    </p>

    <p style="margin:0 0 14px;font-size:10px;">
        <strong>Keputusan:</strong><br>
        <span style="white-space:pre-line;">{{ $meeting->decisions ?: '-' }}</span>
    </p>

    <p style="margin:0 0 6px;font-size:10px;"><strong>Action Items</strong></p>
    <table class="data">
        <thead>
            <tr>
                <th>Task</th>
                <th>PIC</th>
                <th>Due</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($meeting->actionItems as $item)
                <tr>
                    <td>{{ $item->task }}</td>
                    <td>{{ $item->pic_all ? 'ALL TEAM' : $item->pic->name ?? '-' }}</td>
                    <td>{{ $item->due_date?->translatedFormat('d M Y') ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">Belum ada action item.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if ($meeting->wasBlasted())
        <p style="margin-top:12px;font-size:9px;color:#6b6459;">
            Ringkasan MoM ini sudah di-blast ke semua karyawan pada
            {{ $meeting->blasted_at->translatedFormat('d M Y, H:i') }} WIB.
        </p>
    @endif
@endsection
