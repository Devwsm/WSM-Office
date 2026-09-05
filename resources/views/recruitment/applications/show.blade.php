{{--
    recruitment/applications/show.blade.php — Detail Pelamar
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => $application->name, 'navActive' => 'applications'])

@section('content')
    <a href="{{ route('recruitment.applications.index') }}" class="text-xs font-bold text-muted">&larr; Kembali ke
        daftar pelamar</a>

    <div class="mt-3 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-[28px] font-black leading-[1.05] tracking-tight sm:text-[32px]">{{ $application->name }}
            </h1>
            <p class="mt-1 text-sm text-muted">Melamar posisi <strong>{{ $application->jobOpening->title }}</strong>
                &middot; {{ $application->created_at->translatedFormat('d M Y, H:i') }}</p>
        </div>
        <span class="badge-wsm-{{ $application->statusBadgeColor() }} text-sm">{{ $application->statusLabel() }}</span>
    </div>

    <div class="mt-6 grid gap-5 lg:grid-cols-[1.2fr_0.8fr]">
        <div class="grid gap-5">
            <div class="card-wsm-white">
                <p class="field-label-wsm">Kontak</p>
                <div class="mt-2 grid gap-1 text-sm">
                    <p><strong>Email:</strong> {{ $application->email }}</p>
                    <p><strong>Telepon:</strong> {{ $application->phone ?? '—' }}</p>
                </div>
            </div>

            <div class="card-wsm-white">
                <p class="field-label-wsm">Pesan / Motivasi</p>
                <p class="mt-2 whitespace-pre-line text-sm text-[#3a362f]">
                    {{ $application->message ?: '—' }}
                </p>
            </div>

            @if ($application->isConverted())
                <div class="card-wsm-white border border-[#bfe8c9] bg-[#e7f8ec]!">
                    <p class="field-label-wsm text-[#14733b]">Sudah Jadi Karyawan</p>
                    <p class="mt-2 text-sm text-[#14733b]">
                        {{ $application->convertedUser->name ?? 'Akun karyawan' }} sudah dibuatkan lewat pelamar ini.
                    </p>
                </div>
            @elseif ($application->status === 'diterima')
                <div class="card-wsm-white">
                    <p class="field-label-wsm">Buatkan Akun Karyawan</p>
                    <p class="mt-2 text-sm text-muted">
                        Pelamar ini sudah diterima. Buatkan akun login-nya biar bisa langsung dipakai kerja.
                    </p>
                    <a href="{{ route('recruitment.applications.convert', $application) }}"
                        class="btn-wsm-black mt-4 inline-flex">Terima &amp; Buatkan Akun</a>
                </div>
            @endif
        </div>

        <div class="card-wsm-white h-fit">
            <p class="field-label-wsm">Ubah Status &amp; Catatan</p>
            <form method="POST" action="{{ route('recruitment.applications.status', $application) }}"
                class="mt-3 grid gap-3.5">
                @csrf
                @method('PATCH')

                <div>
                    <label class="field-label-wsm mb-1.5">Status</label>
                    <select name="status" class="input-wsm">
                        @foreach ($statuses as $value)
                            <option value="{{ $value }}" @selected($application->status === $value)>
                                {{ ucfirst($value) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="field-label-wsm mb-1.5">Catatan Internal (opsional)</label>
                    <textarea name="notes" rows="4" class="input-wsm"
                        placeholder="Catatan buat tim HRD/Owner, tidak terlihat pelamar.">{{ old('notes', $application->notes) }}</textarea>
                </div>

                <button type="submit" class="btn-wsm-black">Simpan</button>
            </form>
        </div>
    </div>
@endsection
