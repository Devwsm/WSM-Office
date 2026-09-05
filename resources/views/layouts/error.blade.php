{{--
    layouts/error.blade.php
    ---------------------------------------------------------------------
    Layout khusus halaman error (404/403/500/503). Sengaja berdiri
    sendiri (bukan extends layouts.app) karena error bisa kejadian di
    request yang belum tentu ada user login-nya. Gaya visual disamakan
    dengan brand mark & palet WSM (lihat resources/css/app.css).

    Variabel yang dipakai tiap halaman error:
    - $code    : kode status, mis. "404"
    - $title   : judul tab browser
    - $heading : judul besar di halaman
    - $message : penjelasan singkat di bawah heading
    Section:
    - @section('actions') : tombol-tombol aksi (kembali, muat ulang, dst)
    - @section('extra')   : konten tambahan opsional (mis. auto-reload script)
    ---------------------------------------------------------------------
--}}
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Error' }} — WSM Office System</title>
    @vite(['resources/css/app.css'])
</head>

<body class="bg-cream text-ink antialiased">
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-16 text-center">
        <div class="mb-6 grid h-14 w-14 place-items-center rounded-2xl bg-ink text-[11px] font-black text-white">
            WSM
        </div>

        <p class="mb-2 text-[13px] font-black uppercase tracking-wide text-muted">
            Error {{ $code ?? '' }}
        </p>

        <h1 class="max-w-xl text-[32px] font-black leading-[1.05] tracking-tight sm:text-[40px]">
            {{ $heading ?? ($title ?? 'Terjadi Kesalahan') }}
        </h1>

        @if (!empty($message))
            <p class="mt-3 max-w-md text-[15px] text-muted">{{ $message }}</p>
        @endif

        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            @yield('actions')
        </div>

        @yield('extra')
    </div>
</body>

</html>
