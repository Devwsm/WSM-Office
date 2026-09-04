{{--
    owner/employees/edit.blade.php
    ---------------------------------------------------------------------
    Fase 2 — form edit karyawan (role, atasan, data lain). Password
    dikosongkan = tidak diganti.
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Edit Karyawan', 'navActive' => 'employees'])

@section('content')
    <div class="mb-6">
        <h2 class="text-[40px] font-black leading-[0.95] tracking-tight">Edit Karyawan</h2>
        <p class="mt-1 text-[15px] text-muted">{{ $employee->name }} · {{ $employee->email }}</p>
    </div>

    <form method="POST" action="{{ route('owner.employees.update', $employee) }}" class="card-wsm-white">
        @csrf
        @method('PUT')
        @include('owner.employees._form', ['employee' => $employee, 'managers' => $managers])

        <div class="mt-6 flex gap-3">
            <button type="submit" class="btn-wsm-black">Simpan Perubahan</button>
            <a href="{{ route('owner.employees.index') }}" class="btn-wsm-white">Batal</a>
        </div>
    </form>
@endsection
