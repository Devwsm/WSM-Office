@extends('layouts.app', ['title' => 'Edit MoM', 'navActive' => 'modules'])

@section('content')
    <div class="mb-6">
        <a href="{{ route('dashboard.work.meetings.index') }}" class="text-[11px] font-extrabold text-muted">←
            Rapat & Action Item</a>
        <h2 class="mt-2 text-[36px] font-black leading-[0.98] tracking-tight">Edit MoM</h2>
    </div>

    <form method="POST" action="{{ route('dashboard.work.meetings.update', $meeting) }}" class="card-wsm-white">
        @csrf
        @method('PATCH')
        @include('dashboard.work.meetings._form', [
            'meeting' => $meeting,
            'projects' => $projects,
            'employees' => $employees,
        ])

        <div class="mt-6 flex gap-3">
            <button type="submit" class="btn-wsm-black">Simpan Perubahan</button>
            <a href="{{ route('dashboard.work.meetings.index') }}" class="btn-wsm-white">Batal</a>
        </div>
    </form>
@endsection
