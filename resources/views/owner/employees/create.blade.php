{{--
    owner/employees/create.blade.php
    ---------------------------------------------------------------------
    Fase 2 — form tambah karyawan baru.
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Tambah Karyawan', 'navActive' => 'employees'])

@section('content')
    <div class="mb-6">
        <h2 class="text-[40px] font-black leading-[0.95] tracking-tight">Tambah Karyawan</h2>
        <p class="mt-1 text-[15px] text-muted">Buat akun baru dan atur role serta atasannya.</p>
    </div>

    <form method="POST" action="{{ route('owner.employees.store') }}" class="card-wsm-white">
        @csrf
        @include('owner.employees._form', ['employee' => null, 'managers' => $managers])

        <div class="mt-6 flex gap-3">
            <button type="submit" class="btn-wsm-black">Simpan Karyawan</button>
            <a href="{{ route('owner.employees.index') }}" class="btn-wsm-white">Batal</a>
        </div>
    </form>
@endsection
