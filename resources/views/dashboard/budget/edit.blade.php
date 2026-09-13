@extends('layouts.app', ['title' => 'Edit Budget', 'navActive' => 'modules'])

@section('content')
    <div class="mb-6">
        <a href="{{ route('dashboard.budget.index') }}" class="text-[11px] font-extrabold text-muted">← Project
            Budgeting</a>
        <h2 class="mt-2 text-[36px] font-black leading-[0.98] tracking-tight">Edit Budget</h2>
    </div>

    <form method="POST" action="{{ route('dashboard.budget.update', $budget) }}" class="card-wsm-white">
        @csrf
        @method('PATCH')
        @include('dashboard.budget._form', ['budget' => $budget, 'projects' => $projects])

        <div class="mt-6 flex gap-3">
            <button type="submit" class="btn-wsm-black">Simpan Perubahan</button>
            <a href="{{ route('dashboard.budget.index') }}" class="btn-wsm-white">Batal</a>
        </div>
    </form>
@endsection
