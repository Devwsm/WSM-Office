{{--
    owner/organization/index.blade.php
    ---------------------------------------------------------------------
    Fase 2 — org-chart dari relasi manager_id di tabel users. Karyawan
    tanpa manager_id dianggap langsung di bawah Owner (root chart).
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Struktur Organisasi', 'navActive' => 'organization'])

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-[40px] font-black leading-[0.95] tracking-tight">Struktur Organisasi</h2>
            <p class="mt-1 text-[15px] text-muted">{{ $totalKaryawan }} karyawan aktif · disusun dari data atasan
                langsung.</p>
        </div>
        <a href="{{ route('owner.employees.index') }}" class="btn-wsm-white">Kelola Karyawan</a>
    </div>

    <div class="card-wsm overflow-x-auto">
        @if ($roots->isEmpty())
            <p class="text-sm text-muted">Belum ada karyawan. Tambah dari halaman Karyawan dulu.</p>
        @else
            <ul class="flex flex-col gap-3">
                @foreach ($roots as $root)
                    <x-org-node :user="$root" :by-manager="$byManager" />
                @endforeach
            </ul>
        @endif
    </div>
@endsection
