{{--
    recruitment/applications/index.blade.php — Daftar Pelamar
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Pelamar', 'navActive' => 'applications'])

@section('content')
    <h1 class="text-[28px] font-black leading-[1.05] tracking-tight sm:text-[32px]">Pelamar</h1>
    <p class="mt-1 text-sm text-muted">Semua lamaran yang masuk dari halaman Karir.</p>

    <form method="GET" class="card-wsm-white mb-5 mt-6 flex flex-wrap items-end gap-3">
        <div class="min-w-40">
            <label class="field-label-wsm mb-1.5">Status</label>
            <select name="status" class="input-wsm" onchange="this.form.submit()">
                <option value="">Semua</option>
                @foreach ($statuses as $value)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>
                        {{ ucfirst($value) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="min-w-48">
            <label class="field-label-wsm mb-1.5">Lowongan</label>
            <select name="lowongan" class="input-wsm" onchange="this.form.submit()">
                <option value="">Semua</option>
                @foreach ($openings as $opening)
                    <option value="{{ $opening->id }}" @selected(($filters['lowongan'] ?? '') == $opening->id)>
                        {{ $opening->title }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="min-w-56 flex-1">
            <label class="field-label-wsm mb-1.5">Cari nama/email</label>
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="input-wsm" placeholder="Cari...">
        </div>
        <button type="submit" class="btn-wsm-black">Cari</button>
    </form>

    <div class="card-wsm-white overflow-x-auto p-0!">
        <table class="w-full min-w-190 text-left text-sm">
            <thead>
                <tr class="border-b border-line text-xs font-black uppercase tracking-wide text-muted">
                    <th class="px-5 py-3.5">Nama</th>
                    <th class="px-5 py-3.5">Lowongan</th>
                    <th class="px-5 py-3.5">Status</th>
                    <th class="px-5 py-3.5">Masuk</th>
                    <th class="px-5 py-3.5"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($applications as $application)
                    <tr class="border-b border-line last:border-b-0">
                        <td class="px-5 py-3.5">
                            <strong>{{ $application->name }}</strong>
                            <div class="text-xs text-muted">{{ $application->email }}</div>
                        </td>
                        <td class="px-5 py-3.5">{{ $application->jobOpening->title }}</td>
                        <td class="px-5 py-3.5">
                            <span class="badge-wsm-{{ $application->statusBadgeColor() }}">
                                {{ $application->statusLabel() }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-xs text-muted">
                            {{ $application->created_at->translatedFormat('d M Y') }}
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <a href="{{ route('recruitment.applications.show', $application) }}"
                                class="btn-wsm-white py-2! px-3.5! text-xs">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-10 text-center text-sm text-muted">
                            Belum ada pelamar yang masuk.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $applications->links() }}</div>
@endsection
