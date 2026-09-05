{{--
    recruitment/openings/_form.blade.php
    ---------------------------------------------------------------------
    Partial form dipakai bareng oleh create.blade.php & edit.blade.php.
    Variabel yang diharapkan: $opening (null kalau mode tambah).
    ---------------------------------------------------------------------
--}}
@php $opening ??= null; @endphp

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label class="field-label-wsm mb-1.5">Judul Posisi</label>
        <input type="text" name="title" value="{{ old('title', $opening->title ?? '') }}" class="input-wsm" required>
        @error('title')
            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="field-label-wsm mb-1.5">Divisi</label>
        <input type="text" name="division" value="{{ old('division', $opening->division ?? '') }}" class="input-wsm">
    </div>

    <div>
        <label class="field-label-wsm mb-1.5">Tipe Kerja</label>
        <select name="employment_type" class="input-wsm" required>
            @foreach (['full_time' => 'Penuh Waktu', 'part_time' => 'Paruh Waktu', 'contract' => 'Kontrak', 'internship' => 'Magang'] as $value => $label)
                <option value="{{ $value }}" @selected(old('employment_type', $opening->employment_type ?? 'full_time') === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('employment_type')
            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
        @enderror
    </div>

    <div class="sm:col-span-2">
        <label class="field-label-wsm mb-1.5">Deskripsi Pekerjaan</label>
        <textarea name="description" rows="5" class="input-wsm" required>{{ old('description', $opening->description ?? '') }}</textarea>
        @error('description')
            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
        @enderror
    </div>

    <div class="sm:col-span-2">
        <label class="field-label-wsm mb-1.5">Kualifikasi / Syarat (opsional)</label>
        <textarea name="requirements" rows="4" class="input-wsm">{{ old('requirements', $opening->requirements ?? '') }}</textarea>
        @error('requirements')
            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="field-label-wsm mb-1.5">Status</label>
        <select name="status" class="input-wsm" required>
            @foreach (['draft' => 'Draft (belum tayang)', 'published' => 'Tayang (nerima lamaran)', 'closed' => 'Ditutup'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $opening->status ?? 'draft') === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('status')
            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
        @enderror
        @if ($opening)
            <p class="mt-1.5 text-xs text-muted">Link publik: {{ url('/karir/' . $opening->slug) }}</p>
        @else
            <p class="mt-1.5 text-xs text-muted">Link publik dibuat otomatis dari judul setelah disimpan.</p>
        @endif
    </div>
</div>
