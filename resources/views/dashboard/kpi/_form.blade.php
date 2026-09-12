{{--
    dashboard/kpi/_form.blade.php
    ---------------------------------------------------------------------
    Partial dipakai create.blade.php & edit.blade.php. Variabel yang
    diharapkan: $kpi (null kalau mode tambah), $employees.
    ---------------------------------------------------------------------
--}}
@php $kpi ??= null; @endphp

<div class="grid gap-4">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="field-label-wsm mb-1.5">Karyawan</label>
            <select name="employee_id" class="input-wsm" required>
                <option value="">- pilih -</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}" @selected(old('employee_id', $kpi->employee_id ?? '') == $employee->id)>
                        {{ $employee->name }}
                    </option>
                @endforeach
            </select>
            @error('employee_id')
                <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="field-label-wsm mb-1.5">Periode</label>
            <input type="text" name="period" value="{{ old('period', $kpi->period ?? '') }}"
                placeholder="mis. Q3 2026" class="input-wsm" required>
            @error('period')
                <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label class="field-label-wsm mb-1.5">Judul KPI</label>
        <input type="text" name="title" value="{{ old('title', $kpi->title ?? '') }}" class="input-wsm" required>
        @error('title')
            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div>
            <label class="field-label-wsm mb-1.5">Target</label>
            <input type="number" step="0.01" name="target" value="{{ old('target', $kpi->target ?? '') }}"
                class="input-wsm" required>
            @error('target')
                <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="field-label-wsm mb-1.5">Progress Saat Ini</label>
            <input type="number" step="0.01" name="current" value="{{ old('current', $kpi->current ?? 0) }}"
                class="input-wsm" required>
            @error('current')
                <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="field-label-wsm mb-1.5">Unit (opsional)</label>
            <input type="text" name="unit" value="{{ old('unit', $kpi->unit ?? '') }}"
                placeholder="mis. %, pcs, Rp" class="input-wsm">
            @error('unit')
                <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="field-label-wsm mb-1.5">Weight % (opsional)</label>
            <input type="number" step="0.01" min="0" max="100" name="weight"
                value="{{ old('weight', $kpi->weight ?? '') }}" class="input-wsm">
            @error('weight')
                <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="field-label-wsm mb-1.5">Due Date (opsional)</label>
            <input type="date" name="due_date"
                value="{{ old('due_date', optional($kpi?->due_date)->format('Y-m-d')) }}" class="input-wsm">
            @error('due_date')
                <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="field-label-wsm mb-1.5">Status</label>
            <select name="status" class="input-wsm" required>
                @foreach (\App\Models\Kpi::STATUSES as $status)
                    <option value="{{ $status }}" @selected(old('status', $kpi->status ?? 'Active') === $status)>
                        {{ $status }}
                    </option>
                @endforeach
            </select>
            @error('status')
                <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label class="field-label-wsm mb-1.5">Catatan Owner (opsional)</label>
        <textarea name="owner_note" rows="3" class="input-wsm">{{ old('owner_note', $kpi->owner_note ?? '') }}</textarea>
        @error('owner_note')
            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
        @enderror
    </div>
</div>
