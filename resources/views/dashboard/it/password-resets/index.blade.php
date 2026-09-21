{{--
    dashboard/it/password-resets/index.blade.php
    ---------------------------------------------------------------------
    Reset password karyawan oleh IT (modul `it`, level Manage). Daftar
    akun + tombol Reset. Setelah reset, password sementara tampil SEKALI
    di kartu di atas daftar (flash session `reset_result`), lalu hilang
    saat halaman dimuat ulang. Tombol Reset disembunyikan untuk akun yang
    tidak boleh direset user ini (User::canResetPasswordOf()).
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'IT — Reset Password', 'navActive' => 'modules'])

@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3.5">
        <div>
            <a href="{{ route('dashboard.index') }}" class="text-[11px] font-extrabold text-muted">← Dashboard</a>
            <h2 class="mt-2 text-[36px] font-black leading-[0.98] tracking-tight">Reset Password</h2>
            <p class="mt-1 text-[13px] text-muted">Buat password sementara untuk karyawan yang lupa password.</p>
        </div>
    </div>

    <div class="mb-5 flex gap-2">
        <a href="{{ route('dashboard.it.index') }}"
            class="rounded-2xl border border-line bg-white px-3.5 py-2 text-[11px] font-extrabold text-ink">Audit Log</a>
        <a href="{{ route('dashboard.it.changelog.index') }}"
            class="rounded-2xl border border-line bg-white px-3.5 py-2 text-[11px] font-extrabold text-ink">System
            Changelog</a>
        <span class="rounded-2xl bg-ink px-3.5 py-2 text-[11px] font-extrabold text-white">Reset Password</span>
    </div>

    @if (session('reset_result'))
        @php $result = session('reset_result'); @endphp
        <div class="mb-5 rounded-wsm border border-[#bfe3b4] bg-[#f1faee] p-5" data-reset-result>
            <p class="text-sm font-extrabold">Password {{ $result['name'] }} sudah direset</p>
            <p class="mt-1 text-xs text-muted">{{ $result['email'] }}</p>
            <p class="mt-3 text-[11px] font-extrabold uppercase tracking-wide text-muted">Password sementara</p>
            <p class="mt-1 select-all rounded-2xl border border-line bg-white px-4 py-3 font-mono text-lg font-black tracking-wider"
                data-temporary-password>{{ $result['password'] }}</p>
            <p class="mt-3 text-xs text-[#5e5951]">
                Sampaikan ke pemilik akun lewat jalur yang aman. Password ini <strong>hanya tampil sekali</strong> dan
                tidak disimpan di mana pun. Saat login berikutnya, pemilik akun wajib menggantinya, dan semua sesi
                login lamanya sudah dikeluarkan.
            </p>
        </div>
    @endif

    <form method="GET" class="mb-4 flex flex-wrap items-center gap-2">
        <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama, email, atau divisi..."
            class="w-full max-w-xs rounded-2xl border border-line bg-white px-3.5 py-2 text-[11px] font-bold text-ink sm:w-auto">
        <button type="submit" class="btn-wsm-white py-2! px-3.5! text-xs">Cari</button>
        @if ($search)
            <a href="{{ route('dashboard.it.password-resets.index') }}"
                class="text-[11px] font-extrabold text-muted">Reset</a>
        @endif
    </form>

    <div class="card-wsm-white overflow-x-auto p-0">
        <table class="w-full min-w-175 text-left text-sm">
            <thead>
                <tr class="border-b border-line text-[11px] font-black uppercase tracking-wide text-muted">
                    <th class="px-5 py-3.5">Nama</th>
                    <th class="px-5 py-3.5">Role</th>
                    <th class="px-5 py-3.5">Status</th>
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
                                class="badge-wsm-{{ match ($employee->role) {'owner', 'developer' => 'blue','manajer' => 'green','hrd' => 'yellow',default => 'gray'} }}">
                                {{ $employee->roleLabel() }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-xs text-[#5e5951]">
                            @if ($employee->must_change_password)
                                <span class="badge-wsm-yellow">Wajib ganti password</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex justify-end">
                                @if ($me->canResetPasswordOf($employee))
                                    <form method="POST"
                                        action="{{ route('dashboard.it.password-resets.reset', $employee) }}"
                                        data-confirm="{{ $employee->name }} akan dikeluarkan dari semua sesi login dan wajib memakai password sementara yang baru."
                                        data-confirm-title="Reset password {{ $employee->name }}?"
                                        data-confirm-button="Ya, reset" data-confirm-danger="1">
                                        @csrf
                                        <input type="hidden" name="q" value="{{ $search }}">
                                        <button type="submit" class="btn-wsm-red py-2! px-3.5! text-xs">Reset</button>
                                    </form>
                                @else
                                    <span class="text-[11px] font-bold text-muted">
                                        {{ $me->is($employee) ? 'Akun kamu (ganti lewat Profil)' : 'Tidak boleh direset' }}
                                    </span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-5 py-10 text-center text-sm text-muted">Tidak ada karyawan yang cocok.
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
