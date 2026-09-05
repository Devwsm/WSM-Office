@php
    // Saat `php artisan down` aktif, SEMUA request (kecuali yang di-except)
    // lewat middleware CheckForMaintenanceMode dan berakhir sebagai 503 —
    // jadi cara paling akurat untuk tahu ini "maintenance" beneran (bukan
    // 503 lain, mis. dari layanan/queue eksternal yang lagi down) adalah
    // cek status maintenance mode-nya langsung, bukan dari pesan exception.
    $isMaintenance = app()->isDownForMaintenance();

    $retryAfter = null;
    if (method_exists($exception, 'getHeaders')) {
        $retryAfter = $exception->getHeaders()['Retry-After'] ?? null;
    }
@endphp

@extends('layouts.error', [
    'code' => '503',
    'title' => $isMaintenance ? 'Sedang Maintenance' : 'Layanan Tidak Tersedia',
    'heading' => $isMaintenance ? 'WSM Office sedang dalam perbaikan' : 'Layanan sedang tidak tersedia',
    'message' => $isMaintenance ? 'Kami sedang melakukan pemeliharaan sistem sebentar supaya makin lancar dipakai. Coba akses lagi dalam beberapa menit.' : 'Server sedang sibuk atau ada gangguan sementara di sistem kami. Silakan coba beberapa saat lagi.',
])

@section('actions')
    <button type="button" onclick="location.reload()" class="btn-wsm-black">Muat Ulang</button>
    @unless ($isMaintenance)
        <a href="{{ url('/') }}" class="btn-wsm-white">Kembali ke Beranda</a>
    @endunless
@endsection

@if ($isMaintenance)
    @section('extra')
        <p class="mt-4 text-xs text-muted">Halaman ini akan otomatis dimuat ulang setiap 30 detik.</p>
        <script>
            setTimeout(() => location.reload(), 30000);
        </script>
    @endsection
@endif
