{{--
    employee/profile.blade.php
    ---------------------------------------------------------------------
    Isi tab "Profile" di bottom-nav app-mobile — sebelumnya placeholder
    (href="#", TODO Fase 1). Mengikuti pola `renderEmployeeProfile()` di
    prototype: kartu identitas (nama/role/divisi/email) + form ganti
    password. SENGAJA belum ada upload foto profil (belum ada fiturnya
    sama sekali di sistem ini, beda topik dari halaman ini).
    ---------------------------------------------------------------------
--}}
@extends('layouts.employee', ['title' => 'Profile', 'navActive' => 'profile'])

@section('content')
    <div class="employee-section-head mb-5">
        <h2 class="text-[30px] font-black leading-none tracking-tight">Profile</h2>
        <p class="mt-1.5 text-xs text-muted">Data diri & keamanan akun kamu.</p>
    </div>

    <div class="card-wsm-white mb-3.5">
        <div class="flex items-center gap-3">
            <div class="grid h-14 w-14 flex-none place-items-center rounded-2xl bg-ink text-sm font-black text-white">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div class="min-w-0">
                <strong class="block truncate text-sm">{{ $user->name }}</strong>
                <span class="badge-wsm-gray mt-1 inline-flex capitalize">{{ $user->roleLabel() }}</span>
            </div>
        </div>

        <div class="mt-4 grid gap-3 border-t border-line pt-4">
            <div>
                <span class="field-label-wsm">Email</span>
                <p class="mt-1 text-sm">{{ $user->email }}</p>
            </div>
            @if ($user->job_title)
                <div>
                    <span class="field-label-wsm">Jabatan</span>
                    <p class="mt-1 text-sm">{{ $user->job_title }}</p>
                </div>
            @endif
            @if ($user->division)
                <div>
                    <span class="field-label-wsm">Divisi</span>
                    <p class="mt-1 text-sm">{{ $user->division }}</p>
                </div>
            @endif
            @if ($user->manager)
                <div>
                    <span class="field-label-wsm">Atasan</span>
                    <p class="mt-1 text-sm">{{ $user->manager->name }}</p>
                </div>
            @endif
            <div>
                <span class="field-label-wsm">Sisa Cuti Tahunan</span>
                <p class="mt-1 text-sm">{{ $user->remainingAnnualLeaveDays() }} hari</p>
            </div>
        </div>
    </div>

    <div class="card-wsm-white">
        <p class="mb-1 text-sm font-extrabold">Ganti Password</p>
        <p class="mb-4 text-xs text-muted">Minimal 8 karakter. Kamu tetap login setelah ganti password.</p>

        <form method="POST" action="{{ route('employee.profile.password') }}" class="grid gap-3">
            @csrf
            @method('PATCH')

            <div>
                <label class="field-label-wsm">Password Saat Ini</label>
                <input type="password" name="current_password" class="input-wsm mt-1.5" required>
                @error('current_password')
                    <p class="mt-1 text-[11px] font-extrabold text-[#a83d35]">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="field-label-wsm">Password Baru</label>
                <input type="password" name="password" class="input-wsm mt-1.5" required minlength="8">
                @error('password')
                    <p class="mt-1 text-[11px] font-extrabold text-[#a83d35]">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="field-label-wsm">Konfirmasi Password Baru</label>
                <input type="password" name="password_confirmation" class="input-wsm mt-1.5" required minlength="8">
            </div>

            <button type="submit" class="btn-wsm-black mt-1.5 w-full">Simpan Password Baru</button>
        </form>
    </div>
@endsection
