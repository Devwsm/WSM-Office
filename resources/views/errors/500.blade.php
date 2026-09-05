@extends('layouts.error', [
    'code' => '500',
    'title' => 'Terjadi Kesalahan',
    'heading' => 'Ada yang salah di server kami',
    'message' => 'Bukan salah kamu — sistem kami sedang mengalami gangguan. Tim sudah otomatis mendapat catatan errornya. Coba lagi beberapa saat lagi.',
])

@section('actions')
    <button type="button" onclick="location.reload()" class="btn-wsm-black">Muat Ulang</button>
    <a href="{{ url('/') }}" class="btn-wsm-white">Kembali ke Beranda</a>
@endsection
