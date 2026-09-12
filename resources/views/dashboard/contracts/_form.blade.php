{{--
    dashboard/contracts/_form.blade.php
    ---------------------------------------------------------------------
    Partial dipakai create.blade.php & edit.blade.php. Variabel yang
    diharapkan: $contract (null kalau mode tambah), $employees.

    Form ini SENGAJA `enctype="multipart/form-data"` — file upload
    beneran (bukan base64), lihat catatan di ContractController.
    ---------------------------------------------------------------------
--}}
@php $contract ??= null; @endphp

<div class="grid gap-4">
    <div>
        <label class="field-label-wsm mb-1.5">Karyawan</label>
        <select name="employee_id" class="input-wsm" required>
            <option value="">- pilih -</option>
            @foreach ($employees as $employee)
                <option value="{{ $employee->id }}" @selected(old('employee_id', $contract->employee_id ?? '') == $employee->id)>
                    {{ $employee->name }}
                </option>
            @endforeach
        </select>
        @error('employee_id')
            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="field-label-wsm mb-1.5">
            File Kontrak {{ $contract ? '(opsional — kosongkan kalau gak ganti file)' : '' }}
        </label>
        @if ($contract)
            <p class="mb-1.5 text-[11px] text-muted">
                File saat ini: <a href="{{ asset('storage/' . $contract->file_path) }}" target="_blank"
                    class="font-bold text-ink underline">{{ $contract->original_filename }}</a>
            </p>
        @endif
        <input type="file" name="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="input-wsm"
            @required(!$contract)>
        <p class="mt-1 text-[10px] text-muted">PDF, Word, atau gambar — maks 10MB.</p>
        @error('file')
            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="field-label-wsm mb-1.5">Tanggal Mulai (opsional)</label>
            <input type="date" name="start_date"
                value="{{ old('start_date', optional($contract?->start_date)->format('Y-m-d')) }}" class="input-wsm">
            @error('start_date')
                <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="field-label-wsm mb-1.5">Tanggal Selesai (opsional)</label>
            <input type="date" name="end_date"
                value="{{ old('end_date', optional($contract?->end_date)->format('Y-m-d')) }}" class="input-wsm">
            @error('end_date')
                <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label class="field-label-wsm mb-1.5">Catatan (opsional)</label>
        <textarea name="notes" rows="3" class="input-wsm">{{ old('notes', $contract->notes ?? '') }}</textarea>
        @error('notes')
            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
        @enderror
    </div>
</div>
