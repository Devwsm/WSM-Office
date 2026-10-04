{{--
    dashboard/budget/access.blade.php
    ---------------------------------------------------------------------
    Atur siapa yang boleh melihat 1 kategori budget. Halaman sendiri
    (BUKAN modal) karena menyangkut akses ke data anggaran.

    Aturan: tidak ada yang dicentang = terbuka untuk semua yang punya akses
    modul Project Budgeting. Ada yang dicentang = hanya mereka (plus
    Owner/Developer, yang selalu bisa melihat semuanya). Kategori yang
    dibatasi hilang total dari halaman, grafik, PDF, dan Excel orang lain.
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Akses Kategori Budget', 'navActive' => 'modules'])

@section('content')
    <div class="mb-6">
        <a href="{{ route('dashboard.budget.index', ['project_id' => $category->project_id]) }}"
            class="text-[11px] font-extrabold text-muted">← Project Budgeting</a>
        <h2 class="mt-2 text-3xl font-black leading-[0.98] tracking-tight sm:text-[36px]">Akses Kategori</h2>
        <p class="mt-1 text-[13px] text-muted">{{ $category->project?->name }} · <b>{{ $category->name }}</b></p>
    </div>

    <form method="POST" action="{{ route('dashboard.budget.categories.viewers', $category) }}"
        class="card-wsm-white max-w-xl">
        @csrf
        @method('PUT')

        <p class="mb-3 text-[12px] text-muted">Kosongkan semua centang agar kategori terbuka untuk semua orang yang
            punya akses Project Budgeting. Owner dan Developer selalu tetap bisa melihat.</p>

        @if ($people->isEmpty())
            <p class="rounded-2xl border border-dashed border-line p-4 text-[12px] text-muted">Belum ada orang lain yang
                punya akses modul Project Budgeting. Berikan aksesnya dulu lewat Dashboard Access.</p>
        @else
            <div class="grid max-h-96 gap-1 overflow-y-auto rounded-2xl border border-line bg-white p-2">
                @foreach ($people as $person)
                    <label
                        class="flex cursor-pointer items-center gap-2 rounded-xl px-2 py-1.5 text-[12px] font-bold hover:bg-[#f6f2eb]">
                        <input type="checkbox" name="user_ids[]" value="{{ $person->id }}" @checked(in_array($person->id, old('user_ids', $currentIds)))>
                        {{ $person->name }}
                        <span class="text-[10px] font-medium text-muted">{{ ucfirst($person->role) }}</span>
                    </label>
                @endforeach
            </div>
        @endif

        <div class="mt-6 flex gap-3">
            <button type="submit" class="btn-wsm-black">Simpan</button>
            <a href="{{ route('dashboard.budget.index', ['project_id' => $category->project_id]) }}"
                class="btn-wsm-white">Batal</a>
        </div>
    </form>
@endsection
