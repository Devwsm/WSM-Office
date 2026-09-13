@extends('layouts.app', ['title' => 'Edit Dokumen Legal', 'navActive' => 'modules'])

@section('content')
    <div class="mb-6">
        <a href="{{ route('dashboard.legal.index') }}" class="text-[11px] font-extrabold text-muted">←
            Legal</a>
        <h2 class="mt-2 text-[36px] font-black leading-[0.98] tracking-tight">Edit Dokumen Legal</h2>
    </div>

    <form method="POST" action="{{ route('dashboard.legal.update', $document) }}" enctype="multipart/form-data"
        class="card-wsm-white">
        @csrf
        @method('PATCH')
        @include('dashboard.legal._form', ['document' => $document])

        <div class="mt-6 flex gap-3">
            <button type="submit" class="btn-wsm-black">Simpan Perubahan</button>
            <a href="{{ route('dashboard.legal.index') }}" class="btn-wsm-white">Batal</a>
        </div>
    </form>
@endsection
