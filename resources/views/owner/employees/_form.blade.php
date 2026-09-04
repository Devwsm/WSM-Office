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
        <input type="date" name="birth_date"
            value="{{ old('birth_date', optional($employee?->birth_date)->format('Y-m-d')) }}" class="input-wsm">
    </div>
</div>
