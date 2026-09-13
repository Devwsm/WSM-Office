{{--
    dashboard/legal/_form.blade.php
    ---------------------------------------------------------------------
    Partial dipakai create.blade.php & edit.blade.php. Variabel yang
    diharapkan: $document (null kalau mode tambah).

    Form ini SENGAJA `enctype="multipart/form-data"` — file upload
    beneran (bukan base64), sama pola persis dashboard/contracts/_form.
    ---------------------------------------------------------------------
--}}
@php $document ??= null; @endphp

<div class="grid gap-4">
    <div>
        <label class="field-label-wsm mb-1.5">Kategori</label>
        <select name="category" class="input-wsm" required>
            <option value="">- pilih -</option>
            @foreach (\App\Models\LegalDocument::CATEGORIES as $category)
                <option value="{{ $category }}" @selected(old('category', $document->category ?? '') === $category)>
                    {{ $category === 'album' ? 'Album Contracts' : 'Royalty Agreements' }}
                </option>
            @endforeach
        </select>
        @error('category')
            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="field-label-wsm mb-1.5">Judul</label>
        <input type="text" name="title" value="{{ old('title', $document->title ?? '') }}" class="input-wsm"
            placeholder="cth. Kontrak Album 'Senja' — Label X" required>
        @error('title')
            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="field-label-wsm mb-1.5">Pihak Terkait (opsional)</label>
        <input type="text" name="party" value="{{ old('party', $document->party ?? '') }}" class="input-wsm"
            placeholder="cth. Nama label, artist, atau publisher">
        @error('party')
            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="field-label-wsm mb-1.5">
            File Dokumen {{ $document ? '(opsional — kosongkan kalau gak ganti file)' : '' }}
        </label>
        @if ($document)
            <p class="mb-1.5 text-[11px] text-muted">
                File saat ini: <a href="{{ asset('storage/' . $document->file_path) }}" target="_blank"
                    class="font-bold text-ink underline">{{ $document->original_filename }}</a>
            </p>
        @endif
        <input type="file" name="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="input-wsm"
            @required(!$document)>
        <p class="mt-1 text-[10px] text-muted">PDF, Word, atau gambar — maks 10MB.</p>
        @error('file')
            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="field-label-wsm mb-1.5">Tanggal Mulai (opsional)</label>
            <input type="date" name="start_date"
                value="{{ old('start_date', optional($document?->start_date)->format('Y-m-d')) }}" class="input-wsm">
            @error('start_date')
                <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="field-label-wsm mb-1.5">Tanggal Selesai (opsional)</label>
            <input type="date" name="end_date"
                value="{{ old('end_date', optional($document?->end_date)->format('Y-m-d')) }}" class="input-wsm">
            @error('end_date')
                <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label class="field-label-wsm mb-1.5">Catatan (opsional)</label>
        <textarea name="notes" rows="3" class="input-wsm">{{ old('notes', $document->notes ?? '') }}</textarea>
        @error('notes')
            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
        @enderror
    </div>
</div>
