{{--
    dashboard/work/_form.blade.php
    ---------------------------------------------------------------------
    Partial dipakai create.blade.php & edit.blade.php. Variabel yang
    diharapkan: $memo (null kalau mode tambah).
    ---------------------------------------------------------------------
--}}
@php $memo ??= null; @endphp
@php $selectedRecipients = old('recipients', $memo?->recipients->pluck('id')->all() ?? []); @endphp

<div class="grid gap-4" x-data="{ audience: '{{ old('audience', $memo->audience ?? 'semua') }}' }">
    <div>
        <label class="field-label-wsm mb-1.5">Jenis</label>
        <select name="type" class="input-wsm" required>
            <option value="memo" @selected(old('type', $memo->type ?? 'memo') === 'memo')>Memo (pengumuman)</option>
            <option value="mom" @selected(old('type', $memo->type ?? 'memo') === 'mom')>Minutes of Meeting</option>
        </select>
        @error('type')
            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="field-label-wsm mb-1.5">Judul</label>
        <input type="text" name="title" value="{{ old('title', $memo->title ?? '') }}" class="input-wsm" required>
        @error('title')
            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="field-label-wsm mb-1.5">Tanggal Rapat (khusus MoM)</label>
            <input type="date" name="meeting_date"
                value="{{ old('meeting_date', optional($memo?->meeting_date)->format('Y-m-d')) }}" class="input-wsm">
            @error('meeting_date')
                <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="field-label-wsm mb-1.5">Peserta (khusus MoM)</label>
            <input type="text" name="attendees" value="{{ old('attendees', $memo->attendees ?? '') }}"
                placeholder="mis. Whisnu, Kanaya, Aldora" class="input-wsm">
            @error('attendees')
                <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label class="field-label-wsm mb-1.5">Penerima</label>
        <select name="audience" class="input-wsm" x-model="audience">
            <option value="semua">Semua Karyawan</option>
            <option value="tertentu">Karyawan Tertentu</option>
        </select>
        @error('audience')
            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
        @enderror
    </div>

    <div x-show="audience === 'tertentu'" x-cloak>
        <label class="field-label-wsm mb-1.5">Pilih Karyawan</label>
        <div class="max-h-56 overflow-y-auto rounded-wsm border border-line bg-white p-2">
            @foreach ($users as $user)
                <label class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-xs hover:bg-[#f7f4ee]">
                    <input type="checkbox" name="recipients[]" value="{{ $user->id }}" @checked(in_array($user->id, $selectedRecipients))>
                    <span class="font-bold">{{ $user->name }}</span>
                    @if ($user->division)
                        <span class="text-muted">· {{ $user->division }}</span>
                    @endif
                </label>
            @endforeach
        </div>
        @error('recipients')
            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="field-label-wsm mb-1.5">Isi</label>
        <textarea name="content" rows="6" class="input-wsm" required>{{ old('content', $memo->content ?? '') }}</textarea>
        @error('content')
            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
        @enderror
    </div>

    <label class="flex items-center gap-2 text-xs font-extrabold text-[#5e5951]">
        <input type="checkbox" name="pinned" value="1" @checked(old('pinned', $memo->pinned ?? false))>
        Pin di kartu "Info dari Owner" (Home semua karyawan)
    </label>
</div>
