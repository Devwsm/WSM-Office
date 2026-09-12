{{--
    dashboard/work/meetings/_form.blade.php
    ---------------------------------------------------------------------
    Partial dipakai create.blade.php & edit.blade.php. Variabel yang
    diharapkan: $meeting (null kalau mode tambah), $projects, $employees.

    Action items dinamis pakai Alpine (x-data 'items' array) — beda dari
    board Work Tracker yang server-rendered penuh, di sini butuh
    tambah/hapus baris tanpa reload karena jumlah action item per MoM
    gak tetap (padanan tombol "+ Action Item" di prototype).
    ---------------------------------------------------------------------
--}}
@php
    $meeting ??= null;

    // Seed awal Alpine: dari old() input (validasi gagal) atau dari
    // action item existing (mode edit) atau 1 baris kosong (mode tambah).
    $seedActionItems = old('action_items');
    if ($seedActionItems === null) {
        $seedActionItems = $meeting
            ? $meeting->actionItems
                ->map(
                    fn($item) => [
                        'id' => $item->id,
                        'task' => $item->task,
                        'pic_employee_id' => $item->pic_employee_id,
                        'pic_all' => $item->pic_all,
                        'due_date' => optional($item->due_date)->format('Y-m-d'),
                    ],
                )
                ->values()
                ->all()
            : [['id' => null, 'task' => '', 'pic_employee_id' => null, 'pic_all' => false, 'due_date' => null]];
    }

    $selectedAttendeeIds = old('attendees', $meeting?->attendees?->pluck('id')->all() ?? []);
@endphp

<div class="grid gap-4">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
            <label class="field-label-wsm mb-1.5">Tanggal Rapat</label>
            <input type="date" name="date" value="{{ old('date', optional($meeting?->date)->format('Y-m-d')) }}"
                class="input-wsm" required>
            @error('date')
                <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="field-label-wsm mb-1.5">Jam (opsional)</label>
            <input type="time" name="time"
                value="{{ old('time', $meeting?->time ? substr($meeting->time, 0, 5) : '') }}" class="input-wsm">
            @error('time')
                <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="field-label-wsm mb-1.5">Project (opsional)</label>
            <select name="project_id" class="input-wsm">
                <option value="">-</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}" @selected(old('project_id', $meeting?->project_id) == $project->id)>
                        {{ $project->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div>
        <label class="field-label-wsm mb-1.5">Agenda</label>
        <input type="text" name="agenda" value="{{ old('agenda', $meeting->agenda ?? '') }}" class="input-wsm"
            required>
        @error('agenda')
            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="field-label-wsm mb-1.5">Peserta (dari tim)</label>
        <div class="grid grid-cols-2 gap-1.5 rounded-2xl border border-line bg-[#f7f5f0] p-3.5 sm:grid-cols-3">
            @foreach ($employees as $employee)
                <label class="flex items-center gap-1.5 text-xs text-ink">
                    <input type="checkbox" name="attendees[]" value="{{ $employee->id }}" @checked(in_array($employee->id, $selectedAttendeeIds))>
                    {{ $employee->name }}
                </label>
            @endforeach
        </div>
        @error('attendees')
            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="field-label-wsm mb-1.5">Peserta Tambahan (di luar sistem, opsional)</label>
        <input type="text" name="persons_text" value="{{ old('persons_text', $meeting->persons_text ?? '') }}"
            placeholder="mis. klien / tamu yang bukan karyawan" class="input-wsm">
        @error('persons_text')
            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="field-label-wsm mb-1.5">Catatan (opsional)</label>
            <textarea name="notes" rows="4" class="input-wsm">{{ old('notes', $meeting->notes ?? '') }}</textarea>
        </div>
        <div>
            <label class="field-label-wsm mb-1.5">Keputusan (opsional)</label>
            <textarea name="decisions" rows="4" class="input-wsm">{{ old('decisions', $meeting->decisions ?? '') }}</textarea>
        </div>
    </div>

    {{-- Action items — dinamis lewat Alpine, padanan momActionRow() prototype --}}
    <div x-data="{ items: {{ json_encode($seedActionItems) }} }">
        <div class="mb-1.5 flex items-center justify-between">
            <label class="field-label-wsm">Action Items</label>
            <button type="button"
                @click="items.push({ id: null, task: '', pic_employee_id: null, pic_all: false, due_date: null })"
                class="rounded-full border border-line bg-white px-3 py-1 text-[10px] font-extrabold text-ink">+
                Tambah Baris</button>
        </div>

        <div class="grid gap-2">
            <template x-for="(row, index) in items" :key="index">
                <div
                    class="grid grid-cols-1 gap-2 rounded-2xl border border-line bg-white p-3 sm:grid-cols-12 sm:items-start">
                    <input type="hidden" :name="`action_items[${index}][id]`" x-bind:value="row.id">

                    <div class="sm:col-span-5">
                        <input type="text" :name="`action_items[${index}][task]`" x-model="row.task"
                            placeholder="Task / action item" class="input-wsm">
                    </div>

                    <div class="sm:col-span-3">
                        <select :name="`action_items[${index}][pic_employee_id]`" x-model="row.pic_employee_id"
                            x-bind:disabled="row.pic_all" class="input-wsm">
                            <option value="">PIC — pilih</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center gap-1.5 sm:col-span-2">
                        <input type="checkbox" :name="`action_items[${index}][pic_all]`" value="1"
                            x-model="row.pic_all">
                        <span class="text-[10px] font-extrabold text-[#5e5951]">ALL TEAM</span>
                    </div>

                    <div class="sm:col-span-1">
                        <input type="date" :name="`action_items[${index}][due_date]`" x-model="row.due_date"
                            class="input-wsm">
                    </div>

                    <div class="sm:col-span-1">
                        <button type="button" @click="items.splice(index, 1)"
                            class="w-full rounded-xl border border-[#e3c9c4] bg-[#fff3f1] px-2 py-2 text-[10px] font-extrabold text-[#a83d35]">Hapus</button>
                    </div>
                </div>
            </template>
        </div>
        @error('action_items.*.task')
            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
        @enderror
    </div>

    <label class="flex items-center gap-2 text-xs font-extrabold text-[#5e5951]">
        <input type="checkbox" name="sync_to_tracker" value="1" @checked(old('sync_to_tracker', true))>
        Masukkan action items ke Work Tracker otomatis
    </label>
</div>
