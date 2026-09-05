{{--
    recruitment/openings/index.blade.php — Daftar Lowongan
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Lowongan', 'navActive' => 'openings'])

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-[28px] font-black leading-[1.05] tracking-tight sm:text-[32px]">Lowongan</h1>
            <p class="mt-1 text-sm text-muted">Kelola posisi yang dibuka & tayang di halaman Karir.</p>
        </div>
        <a href="{{ route('recruitment.openings.create') }}" class="btn-wsm-black">+ Tambah Lowongan</a>
    </div>

    <form method="GET" class="card-wsm-white mb-5 mt-6 flex flex-wrap items-end gap-3">
        <div class="min-w-40">
            <label class="field-label-wsm mb-1.5">Status</label>
            <select name="status" class="input-wsm" onchange="this.form.submit()">
                <option value="">Semua</option>
                @foreach (['draft' => 'Draft', 'published' => 'Tayang', 'closed' => 'Ditutup'] as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </form>

    <div class="card-wsm-white overflow-x-auto p-0!">
        <table class="w-full min-w-180 text-left text-sm">
            <thead>
                <tr class="border-b border-line text-xs font-black uppercase tracking-wide text-muted">
                    <th class="px-5 py-3.5">Posisi</th>
                    <th class="px-5 py-3.5">Divisi</th>
                    <th class="px-5 py-3.5">Status</th>
                    <th class="px-5 py-3.5">Pelamar</th>
                    <th class="px-5 py-3.5"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($openings as $opening)
                    <tr class="border-b border-line last:border-b-0">
                        <td class="px-5 py-3.5">
                            <strong>{{ $opening->title }}</strong>
                            <div class="text-xs text-muted">{{ $opening->employmentTypeLabel() }}</div>
                        </td>
                        <td class="px-5 py-3.5">{{ $opening->division ?? '—' }}</td>
                        <td class="px-5 py-3.5">
                            <span
                                class="badge-wsm-{{ match ($opening->status) {'published' => 'green','closed' => 'red',default => 'gray'} }}">
                                {{ $opening->statusLabel() }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5">{{ $opening->applications_count }}</td>
                        <td class="px-5 py-3.5 text-right">
                            @if ($opening->status === 'published')
                                <a href="{{ route('public.careers.show', $opening) }}" target="_blank"
                                    class="btn-wsm-white py-2! px-3.5! text-xs">Lihat Publik</a>
                            @endif
                            <a href="{{ route('recruitment.openings.edit', $opening) }}"
                                class="btn-wsm-white py-2! px-3.5! text-xs">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-10 text-center text-sm text-muted">
                            Belum ada lowongan. Klik "+ Tambah Lowongan" buat bikin yang pertama.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $openings->links() }}</div>
@endsection
