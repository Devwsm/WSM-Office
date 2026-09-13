@php $royalty ??= null; @endphp

<div class="grid gap-4">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="field-label-wsm mb-1.5">Judul</label>
            <input type="text" name="title" value="{{ old('title', $royalty->title ?? '') }}" class="input-wsm"
                required>
            @error('title')
                <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="field-label-wsm mb-1.5">Periode (opsional)</label>
            <input type="month" name="period" value="{{ old('period', $royalty->period ?? '') }}" class="input-wsm">
            @error('period')
                <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="field-label-wsm mb-1.5">Sumber (opsional)</label>
            <input type="text" name="source" value="{{ old('source', $royalty->source ?? '') }}"
                placeholder="mis. Spotify, YouTube, Label" class="input-wsm">
            @error('source')
                <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="field-label-wsm mb-1.5">Status</label>
            <select name="status" class="input-wsm" required>
                @foreach (\App\Models\RoyaltyEntry::STATUSES as $status)
                    <option value="{{ $status }}" @selected(old('status', $royalty->status ?? 'Estimated') === $status)>
                        {{ $status }}
                    </option>
                @endforeach
            </select>
            @error('status')
                <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
            <label class="field-label-wsm mb-1.5">Gross (Rp)</label>
            <input type="number" step="1000" name="gross" value="{{ old('gross', $royalty->gross ?? '') }}"
                class="input-wsm" min="0" required>
            @error('gross')
                <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="field-label-wsm mb-1.5">Share (%)</label>
            <input type="number" step="0.01" name="share_pct"
                value="{{ old('share_pct', $royalty->share_pct ?? 100) }}" class="input-wsm" min="0"
                max="100" required>
            @error('share_pct')
                <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="field-label-wsm mb-1.5">Recoup (Rp)</label>
            <input type="number" step="1000" name="recoup" value="{{ old('recoup', $royalty->recoup ?? 0) }}"
                class="input-wsm" min="0">
            @error('recoup')
                <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label class="field-label-wsm mb-1.5">Catatan (opsional)</label>
        <textarea name="note" rows="3" class="input-wsm">{{ old('note', $royalty->note ?? '') }}</textarea>
        @error('note')
            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
        @enderror
    </div>
</div>
