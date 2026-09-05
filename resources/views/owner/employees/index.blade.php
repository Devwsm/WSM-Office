{{--
    owner/employees/index.blade.php
    ---------------------------------------------------------------------
    Fase 2 — daftar karyawan/manajer/HRD, filter role + pencarian, toggle
    lihat yang nonaktif (soft deleted), tombol tambah/edit/nonaktifkan.
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Karyawan', 'navActive' => 'employees'])

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-[40px] font-black leading-[0.95] tracking-tight">Karyawan</h2>
            <p class="mt-1 text-[15px] text-muted">Kelola akun, role, dan atasan tiap karyawan.</p>
        </div>
        <a href="{{ route('owner.employees.create') }}" class="btn-wsm-black">+ Tambah Karyawan</a>
    </div>

    <form method="GET" class="card-wsm-white mb-5 flex flex-wrap items-end gap-3">
        <div class="min-w-45 flex-1">
            <label class="field-label-wsm mb-1.5">Cari</label>
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nama, email, divisi..."
                class="input-wsm">
        </div>
        <div class="w-44">
            <label class="field-label-wsm mb-1.5">Role</label>
            <select name="role" class="input-wsm">
                <option value="">Semua Role</option>
                @foreach (['owner' => 'Owner', 'manajer' => 'Manajer', 'hrd' => 'HRD', 'karyawan' => 'Karyawan'] as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['role'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <label class="flex items-center gap-2 pb-3 text-sm font-semibold text-[#5e5951]">
            <input type="checkbox" name="nonaktif" value="1" @checked(!empty($filters['nonaktif']))>
            Tampilkan yang nonaktif
        </label>
        <button type="submit" class="btn-wsm-white">Terapkan</button>
    </form>

    <div class="card-wsm-white overflow-x-auto p-0">
        <table class="w-full min-w-175 text-left text-sm">
            <thead>
                <tr class="border-b border-line text-[11px] font-black uppercase tracking-wide text-muted">
                    <th class="px-5 py-3.5">Nama</th>
                    <th class="px-5 py-3.5">Role</th>
                    <th class="px-5 py-3.5">Divisi / Jabatan</th>
                    <th class="px-5 py-3.5">Atasan</th>
                    <th class="px-5 py-3.5 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employees as $employee)
                    <tr class="border-b border-line last:border-0">
                        <td class="px-5 py-4">
                            <strong class="block">{{ $employee->name }}</strong>
                            <span class="text-xs text-muted">{{ $employee->email }}</span>
                        </td>
                        <td class="px-5 py-4">
                            <span
                                class="badge-wsm-{{ match ($employee->role) {'owner' => 'blue','manajer' => 'green','hrd' => 'yellow',default => 'gray'} }}">
                                {{ $employee->roleLabel() }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-[#5e5951]">
                            {{ $employee->division ?? '—' }}
                            @if ($employee->job_title)
                                <span class="text-xs text-muted">· {{ $employee->job_title }}</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-[#5e5951]">{{ $employee->manager->name ?? '—' }}</td>
                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-2">
                                @if ($employee->trashed())
                                    <form method="POST" action="{{ route('owner.employees.restore', $employee->id) }}"
                                        data-confirm="{{ $employee->name }} akan bisa login & muncul lagi di daftar aktif."
                                        data-confirm-title="Aktifkan kembali?" data-confirm-button="Ya, aktifkan">
                                        @csrf
                                        <button type="submit" class="btn-wsm-white py-2! px-3.5! text-xs">Aktifkan</button>
                                    </form>
                                @else
                                    <a href="{{ route('owner.employees.edit', $employee) }}"
                                        class="btn-wsm-white py-2! px-3.5! text-xs">Edit</a>
                                    <form method="POST" action="{{ route('owner.employees.destroy', $employee) }}"
                                        data-confirm="{{ $employee->name }} tidak akan bisa login lagi, tapi riwayat datanya tetap tersimpan."
                                        data-confirm-title="Nonaktifkan {{ $employee->name }}?"
                                        data-confirm-button="Ya, nonaktifkan" data-confirm-danger="1">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="btn-wsm-red py-2! px-3.5! text-xs">Nonaktifkan</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-10 text-center text-sm text-muted">Tidak ada karyawan yang cocok.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">
        {{ $employees->links() }}
    </div>
@endsection
