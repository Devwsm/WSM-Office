@php
    $customMessage = $exception->getMessage() ?? null;
@endphp

@extends('layouts.error', [
    'code' => '403',
    'title' => 'Akses Ditolak',
    'heading' => 'Kamu tidak punya akses ke halaman ini',
    'message' => $customMessage ?: 'Akun kamu tidak diizinkan membuka halaman ini. Kalau menurut kamu ini keliru, hubungi Owner/HRD.',
])

@section('actions')
    <a href="{{ url('/') }}" class="btn-wsm-black">Kembali ke Beranda</a>
    <button type="button" onclick="history.back()" class="btn-wsm-white">Halaman Sebelumnya</button>
@endsection
