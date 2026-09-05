{{--
    recruitment/applications/convert.blade.php — Terima & Buatkan Akun
    ---------------------------------------------------------------------
    Field-nya sengaja disamakan dengan owner/employees/_form.blade.php
    (hasil akhirnya sama-sama bikin baris `users` baru) — bedanya nama &
    email di sini sudah keisi duluan dari data lamaran.
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Buatkan Akun Karyawan', 'navActive' => 'applications'])

@section('content')
    <a href="{{ route('recruitment.applications.show', $application) }}" class="text-xs font-bold text-muted">&larr;
        Kembali ke detail pelamar</a>

    <h1 class="mt-3 text-[28px] font-black leading-[1.05] tracking-tight sm:text-[32px]">Terima &amp; Buatkan Akun</h1>
    <p class="mt-1 text-sm text-muted">
        Bikin akun login untuk <strong>{{ $application->name }}</strong> berdasarkan lamaran di posisi
        {{ $application->jobOpening->title }}.
    </p>

    <form method="POST" action="{{ route('recruitment.applications.convert.store', $application) }}"
        class="card-wsm-white mt-6" data-confirm="Akun karyawan baru akan langsung aktif dan bisa dipakai login."
        data-confirm-title="Buatkan akun untuk {{ $application->name }}?" data-confirm-button="Ya, buatkan akun">
        @csrf

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="field-label-wsm mb-1.5">Nama Lengkap</label>
                <input type="text" name="name" value="{{ old('name', $application->name) }}" class="input-wsm"
                    required>
                @error('name')
                    <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="field-label-wsm mb-1.5">Email</label>
                <input type="email" name="email" value="{{ old('email', $application->email) }}" class="input-wsm"
                    required>
                @error('email')
                    <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="field-label-wsm mb-1.5">Password</label>
                <input type="password" name="password" class="input-wsm" required>
                @error('password')
                    <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="field-label-wsm mb-1.5">Role</label>
                <select name="role" class="input-wsm" required>
                    @foreach (['karyawan' => 'Karyawan', 'manajer' => 'Manajer', 'hrd' => 'HRD', 'owner' => 'Owner'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('role', 'karyawan') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('role')
                    <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="field-label-wsm mb-1.5">Atasan Langsung</label>
                <select name="manager_id" class="input-wsm">
                    <option value="">— Tidak ada (langsung di bawah Owner) —</option>
                    @foreach ($managers as $manager)
                        <option value="{{ $manager->id }}" @selected((int) old('manager_id', 0) === $manager->id)>
                            {{ $manager->name }} ({{ $manager->roleLabel() }})
                        </option>
                    @endforeach
                </select>
                @error('manager_id')
                    <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="field-label-wsm mb-1.5">Divisi</label>
                <input type="text" name="division" value="{{ old('division', $application->jobOpening->division) }}"
                    class="input-wsm">
            </div>

            <div>
                <label class="field-label-wsm mb-1.5">Jabatan</label>
                <input type="text" name="job_title" value="{{ old('job_title', $application->jobOpening->title) }}"
                    class="input-wsm">
            </div>

            <div>
                <label class="field-label-wsm mb-1.5">Tanggal Bergabung</label>
                <input type="date" name="join_date" value="{{ old('join_date', now()->format('Y-m-d')) }}"
                    class="input-wsm">
            </div>

            <div>
                <label class="field-label-wsm mb-1.5">Jatah Cuti Tahunan (hari)</label>
                <input type="number" name="annual_leave_entitlement" value="{{ old('annual_leave_entitlement', 12) }}"
                    class="input-wsm" min="0" max="60">
            </div>

            <div>
                <label class="field-label-wsm mb-1.5">Tanggal Lahir (opsional)</label>
                <input type="date" name="birth_date" max="{{ now()->subDay()->format('Y-m-d') }}"
                    value="{{ old('birth_date') }}" class="input-wsm">
            </div>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            <button type="submit" class="btn-wsm-black">
                Buatkan Akun
            </button>
            <a href="{{ route('recruitment.applications.show', $application) }}" class="btn-wsm-white">Batal</a>
        </div>
    </form>
@endsection
