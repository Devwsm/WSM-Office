{{--
    owner/employees/_form.blade.php
    ---------------------------------------------------------------------
    Partial form dipakai bareng oleh create.blade.php & edit.blade.php.
    Variabel yang diharapkan: $employee (null kalau mode tambah), $managers.
    ---------------------------------------------------------------------
--}}
@php $employee ??= null; @endphp

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div>
        <label class="field-label-wsm mb-1.5">Nama Lengkap</label>
        <input type="text" name="name" value="{{ old('name', $employee->name ?? '') }}" class="input-wsm" required>
        @error('name')
            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="field-label-wsm mb-1.5">Email</label>
        <input type="email" name="email" value="{{ old('email', $employee->email ?? '') }}" class="input-wsm"
            required>
        @error('email')
            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="field-label-wsm mb-1.5">
            {{ $employee ? 'Password Baru (opsional)' : 'Password' }}
        </label>
        <input type="password" name="password" class="input-wsm"
            placeholder="{{ $employee ? 'Kosongkan jika tidak diganti' : '' }}" {{ $employee ? '' : 'required' }}>
        @error('password')
            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="field-label-wsm mb-1.5">Role</label>
        <select name="role" class="input-wsm" required>
            @foreach (['karyawan' => 'Karyawan', 'manajer' => 'Manajer', 'hrd' => 'HRD', 'owner' => 'Owner'] as $value => $label)
                <option value="{{ $value }}" @selected(old('role', $employee->role ?? 'karyawan') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('role')
            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="field-label-wsm mb-1.5">Atasan Langsung</label>
        <select name="manager_id" class="input-wsm">
            <option value="">— Tidak ada (langsung di bawah Owner) —</option>
            @foreach ($managers as $manager)
                <option value="{{ $manager->id }}" @selected((int) old('manager_id', $employee->manager_id ?? 0) === $manager->id)>
                    {{ $manager->name }} ({{ $manager->roleLabel() }})
                </option>
            @endforeach
        </select>
        @error('manager_id')
            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="field-label-wsm mb-1.5">Divisi</label>
        <input type="text" name="division" value="{{ old('division', $employee->division ?? '') }}"
            class="input-wsm">
    </div>

    <div>
        <label class="field-label-wsm mb-1.5">Jabatan</label>
        <input type="text" name="job_title" value="{{ old('job_title', $employee->job_title ?? '') }}"
            class="input-wsm">
    </div>

    <div>
        <label class="field-label-wsm mb-1.5">Tanggal Bergabung</label>
        <input type="date" name="join_date"
            value="{{ old('join_date', optional($employee?->join_date)->format('Y-m-d')) }}" class="input-wsm">
    </div>

    <div>
        <label class="field-label-wsm mb-1.5">Jatah Cuti Tahunan (hari)</label>
        <input type="number" name="annual_leave_entitlement"
            value="{{ old('annual_leave_entitlement', $employee->annual_leave_entitlement ?? 12) }}" class="input-wsm"
            min="0" max="60">
    </div>

    <div>
        <label class="field-label-wsm mb-1.5">Tanggal Lahir</label>
        <input type="date" name="birth_date" max="{{ now()->subDay()->format('Y-m-d') }}"
            value="{{ old('birth_date', optional($employee?->birth_date)->format('Y-m-d')) }}" class="input-wsm">
    </div>
</div>

{{-- Fase 12 — data Payroll, ditunda dari Fase 7. Ketiga field ini opsional:
     karyawan tanpa "Gaji Pokok" diisi otomatis dilewati pas generate
     Payroll (lihat PayrollController::generate()), bukan dianggap 0. --}}
<div class="mt-5 border-t border-[#eee8df] pt-4">
    <p class="mb-3 text-xs font-extrabold uppercase tracking-wide text-[#5e5952]">Data Payroll (opsional)</p>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
            <label class="field-label-wsm mb-1.5">Gaji Pokok (Rp)</label>
            <input type="number" name="salary_base" min="0" step="1000"
                value="{{ old('salary_base', $employee->salary_base ?? '') }}" class="input-wsm">
            <p class="mt-1 text-[11px] text-muted">Kosongkan kalau karyawan ini belum digaji lewat sistem
                (gak akan muncul di daftar generate Payroll).</p>
            @error('salary_base')
                <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="field-label-wsm mb-1.5">Target Jam Kerja/Hari</label>
            <input type="number" name="target_hours_per_day" min="1" max="24"
                value="{{ old('target_hours_per_day', $employee->target_hours_per_day ?? '') }}" class="input-wsm">
            @error('target_hours_per_day')
                <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="field-label-wsm mb-1.5">Flat Rate Lembur (Rp/pengajuan)</label>
            <input type="number" name="flat_overtime_rate" min="0" step="1000"
                value="{{ old('flat_overtime_rate', $employee->flat_overtime_rate ?? '') }}" class="input-wsm">
            <p class="mt-1 text-[11px] text-muted">Dikali jumlah Lembur disetujui bulan itu — bukan dihitung per
                jam durasi.</p>
            @error('flat_overtime_rate')
                <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>
