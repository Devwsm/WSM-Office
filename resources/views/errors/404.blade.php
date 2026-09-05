@extends('layouts.error', [
    'code' => '404',
    'title' => 'Halaman Tidak Ditemukan',
    'heading' => 'Halaman yang kamu cari tidak ada',
    'message' => 'Mungkin alamatnya salah ketik, atau halamannya sudah dipindah/dihapus.',
])

@section('actions')
    <a href="{{ url('/') }}" class="btn-wsm-black">Kembali ke Beranda</a>
    <button type="button" onclick="history.back()" class="btn-wsm-white">Halaman Sebelumnya</button>
@endsection
