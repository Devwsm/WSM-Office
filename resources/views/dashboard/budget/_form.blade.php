@php $budget ??= null; @endphp

<div class="grid gap-4">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="field-label-wsm mb-1.5">Project</label>
            <select name="project_id" class="input-wsm" required>
                <option value="">- pilih -</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}" @selected(old('project_id', $budget->project_id ?? '') == $project->id)>
                        {{ $project->name }}
                    </option>
                @endforeach
            </select>
            @error('project_id')
                <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="field-label-wsm mb-1.5">Kategori</label>
            <input type="text" name="category" value="{{ old('category', $budget->category ?? '') }}"
                placeholder="mis. Creative, Marketing, Production" class="input-wsm" required>
            @error('category')
                <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label class="field-label-wsm mb-1.5">Item</label>
        <input type="text" name="item" value="{{ old('item', $budget->item ?? '') }}" class="input-wsm" required>
        @error('item')
            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="field-label-wsm mb-1.5">Budget (Rp)</label>
            <input type="number" step="1000" name="budget" value="{{ old('budget', $budget->budget ?? '') }}"
                class="input-wsm" min="0" required>
            @error('budget')
                <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="field-label-wsm mb-1.5">Actual (Rp)</label>
            <input type="number" step="1000" name="actual" value="{{ old('actual', $budget->actual ?? 0) }}"
                class="input-wsm" min="0">
            @error('actual')
                <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label class="field-label-wsm mb-1.5">Catatan (opsional)</label>
        <textarea name="note" rows="3" class="input-wsm">{{ old('note', $budget->note ?? '') }}</textarea>
        @error('note')
            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
        @enderror
    </div>
</div>
