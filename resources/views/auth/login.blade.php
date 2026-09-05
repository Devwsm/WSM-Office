{{--
    auth/login.blade.php
    ---------------------------------------------------------------------
    Halaman login tunggal untuk Owner, Manajer, dan Karyawan.
    Gaya disamakan dengan "login-card" pada prototype W.O.S 2.0.
    ---------------------------------------------------------------------
--}}
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — WSM Office System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="flex min-h-screen items-center justify-center bg-cream p-4 antialiased">
    @include('partials.flash-data')

    <div class="w-full max-w-107.5 rounded-4xl border border-line bg-paper p-7 shadow-wsm">
        <div class="mb-5 grid h-14 w-14 place-items-center rounded-2xl bg-ink text-xs font-black text-white">
            WSM
        </div>
        <h1 class="text-[34px] font-black leading-none tracking-tight">WSM Office</h1>
        <p class="mt-2 text-sm text-muted">Masuk dengan akun kamu untuk lanjut ke dashboard.</p>

        @if ($errors->any())
            <div class="mt-5 rounded-2xl border border-[#f1c7c2] bg-[#fff0ee] px-4 py-3 text-sm text-[#a83d35]">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="mt-6 grid gap-4">
            @csrf
            <div class="grid gap-1.5">
                <label class="field-label-wsm">Email</label>
                <input type="email" name="email" required autofocus class="input-wsm">
            </div>
            <div class="grid gap-1.5">
                <label class="field-label-wsm">Password</label>
                <input type="password" name="password" required class="input-wsm">
            </div>
            <label class="flex items-center gap-2 text-xs font-semibold text-muted">
                <input type="checkbox" name="remember" class="rounded border-[#dcd6cd]">
                Ingat saya
            </label>
            <button type="submit" class="btn-wsm-black w-full">
                Masuk
            </button>
        </form>
    </div>
</body>

</html>
