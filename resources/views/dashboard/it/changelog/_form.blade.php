{{--
    dashboard/it/changelog/_form.blade.php
    ---------------------------------------------------------------------
    Partial dipakai create.blade.php & edit.blade.php. Variabel yang
    diharapkan: $changelog (null kalau mode tambah).

    `modules` & `changes` di form ini tetap INPUT TEKS BIASA (bukan
    dynamic-row JS) — modules dipisah koma, changes 1 baris = 1 bullet
    di textarea — sama persis behaviour prototype `saveCustomChangeLog`.
    Controller yang urus split jadi array sebelum disimpan.
    ---------------------------------------------------------------------
--}}
@php $changelog ??= null; @endphp

<div class="grid gap-4">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="field-label-wsm mb-1.5">Versi</label>
            <input type="text" name="version" value="{{ old('version', $changelog->version ?? '') }}" class="input-wsm"
                placeholder="cth. v1.4.0" required>
            @error('version')
                <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="field-label-wsm mb-1.5">Tanggal Rilis</label>
            <input type="date" name="release_date"
                value="{{ old('release_date', optional($changelog?->release_date)->format('Y-m-d')) }}"
                class="input-wsm" required>
            @error('release_date')
                <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label class="field-label-wsm mb-1.5">Status</label>
        <select name="status" class="input-wsm" required>
            @foreach (\App\Models\SystemChangelog::STATUSES as $status)
                <option value="{{ $status }}" @selected(old('status', $changelog->status ?? 'Planned') === $status)>
                    {{ $status }}
                </option>
            @endforeach
        </select>
        @error('status')
            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="field-label-wsm mb-1.5">Judul</label>
        <input type="text" name="title" value="{{ old('title', $changelog->title ?? '') }}" class="input-wsm"
            placeholder="cth. Modul Legal & Payroll" required>
        @error('title')
            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="field-label-wsm mb-1.5">Modul Terkait (opsional, pisah koma)</label>
        <input type="text" name="modules"
            value="{{ old('modules', $changelog ? implode(', ', $changelog->modules ?? []) : '') }}" class="input-wsm"
            placeholder="cth. Legal, Payroll, IT">
        @error('modules')
            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="field-label-wsm mb-1.5">Daftar Perubahan (1 baris = 1 poin)</label>
        <textarea name="changes" rows="5" class="input-wsm"
            placeholder="cth.&#10;Modul Legal buat Album Contracts & Royalty Agreements&#10;Perbaikan validasi tanggal kontrak">{{ old('changes', $changelog ? implode("\n", $changelog->changes ?? []) : '') }}</textarea>
        @error('changes')
            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
        @enderror
    </div>
</div>
