{{--
    dashboard/locked.blade.php
    ---------------------------------------------------------------------
    Layar unlock "Lock Dashboard" — padanan overlay `openOwnerLogin()`
    di prototype v18 (yang minta 1 "password management" bersama).
    Versi ini minta password akun user sendiri, sesuai adaptasi yang
    dijelaskan di README bagian "Audit posisi UI/UX vs prototype v18".

    Sengaja bikin <html> sendiri (bukan extends layouts.app) — logikanya
    sama seperti layouts.error: area sidebar-nya sendiri yang lagi
    dikunci, jadi gak boleh nampilin sidebar itu di sini.
    ---------------------------------------------------------------------
--}}
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Terkunci — WSM Office System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="flex min-h-screen items-center justify-center bg-cream p-4 antialiased">
    @include('partials.flash-data')

    <div class="w-full max-w-107.5 rounded-4xl border border-line bg-paper p-7 shadow-wsm">
        <div class="mb-5 grid h-14 w-14 place-items-center rounded-2xl bg-ink text-xs font-black text-white">
            WSM
        </div>
        <p class="text-[13px] font-black uppercase tracking-wide text-muted">Dashboard Terkunci</p>
        <h1 class="mt-1 text-[30px] font-black leading-none tracking-tight">Masukkan Password</h1>
        <p class="mt-2 text-sm text-muted">
            Masukkan password akun kamu untuk lanjut ke Karyawan, KPI, Work Control, persetujuan, payroll, dan
            pengaturan.
        </p>

        @if ($errors->any())
            <div class="mt-5 rounded-2xl border border-[#f1c7c2] bg-[#fff0ee] px-4 py-3 text-sm text-[#a83d35]">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('dashboard.lock.unlock') }}" class="mt-6 grid gap-4">
            @csrf
            <div class="grid gap-1.5">
                <label class="field-label-wsm">Password</label>
                <input type="password" name="password" required autofocus class="input-wsm">
            </div>
            <button type="submit" class="btn-wsm-black w-full">Buka Dashboard</button>
        </form>

        <form method="POST" action="{{ route('dashboard.lock.cancel') }}" class="mt-2.5">
            @csrf
            <button type="submit" class="btn-wsm-white w-full">Kembali</button>
        </form>
    </div>
</body>

</html>
