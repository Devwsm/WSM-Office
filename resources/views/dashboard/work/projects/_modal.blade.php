{{--
    dashboard/work/projects/_modal.blade.php
    ---------------------------------------------------------------------
    2026-09-28 — Modal Tambah/Edit Project. Dulu form project nempel
    di halaman (split screen di prototype) atau ditumpuk di modal "Kelola
    Projects" board kanban; sekarang 1 modal bersih dipakai halaman
    Projects. Dibuka lewat event window `wt-project-modal`:
        window.dispatchEvent(new CustomEvent('wt-project-modal', { detail: project|null }))
    Kalau validasi gagal (redirect back + $errors), modal otomatis
    dibuka lagi dengan input lama; `_project_id` disimpan biar mode edit
    tetap kebawa.
    ---------------------------------------------------------------------
--}}
<div x-data="{ open: {{ $errors->any() && old('name') !== null ? 'true' : 'false' }} }" @wt-project-modal.window="open = true; wtFillProjectForm($event.detail)"
    @keydown.escape.window="open = false" x-show="open" x-cloak
    class="fixed inset-0 z-50 grid place-items-end bg-black/40 p-0 sm:place-items-center sm:p-4">
    <div @click.outside="open = false"
        class="max-h-[92vh] w-full max-w-xl overflow-y-auto rounded-t-4xl bg-cream p-5 sm:rounded-4xl">
        <div class="mb-3 flex items-start justify-between gap-3">
            <div>
                <p class="text-[10px] font-extrabold uppercase tracking-widest text-muted" id="wtProjectEyebrow">Add New
                    Project</p>
                <h3 class="text-xl font-black" id="wtProjectFormTitle">Create Project</h3>
            </div>
            <button type="button" @click="open = false"
                class="grid h-9 w-9 flex-none place-items-center rounded-2xl bg-[#ece7dd]">✕</button>
        </div>

        @if ($errors->any() && old('name') !== null)
            <div class="mb-3 rounded-2xl bg-[#ffded8] px-3.5 py-2.5 text-xs font-bold text-[#9b392f]">
                {{ $errors->first() }}
            </div>
        @endif

        <form id="wtProjectForm" method="POST" action="{{ route('dashboard.work.tracker.projects.store') }}"
            class="grid gap-3">
            @csrf
            <span id="wtProjectFormMethod"></span>
            <input type="hidden" name="_project_id" id="wtProjectId" value="{{ old('_project_id') }}">
            <div class="grid gap-1">
                <label class="text-[10px] font-extrabold uppercase text-muted">Project Name</label>
                <input name="name" id="wtProjectName" required value="{{ old('name') }}"
                    placeholder="Contoh: Map of Feelings Phase 2 / Releases 2027"
                    class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm">
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="grid gap-1">
                    <label class="text-[10px] font-extrabold uppercase text-muted">Start Date</label>
                    <input type="date" name="start_date" id="wtProjectStart" value="{{ old('start_date') }}"
                        class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm">
                </div>
                <div class="grid gap-1">
                    <label class="text-[10px] font-extrabold uppercase text-muted">End Date</label>
                    <input type="date" name="end_date" id="wtProjectEnd" value="{{ old('end_date') }}"
                        class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm">
                </div>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="grid gap-1">
                    <label class="text-[10px] font-extrabold uppercase text-muted">Priority</label>
                    <select name="priority" id="wtProjectPriority"
                        class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm">
                        @foreach (\App\Models\Project::PRIORITIES as $priority)
                            <option value="{{ $priority }}" @selected(old('priority', 'Medium') === $priority)>{{ $priority }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="grid gap-1">
                    <label class="text-[10px] font-extrabold uppercase text-muted">Status</label>
                    <select name="status" id="wtProjectStatus"
                        class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm">
                        @foreach (\App\Models\Project::STATUSES as $status)
                            <option value="{{ $status }}" @selected(old('status', 'On Development') === $status)>{{ $status }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="grid gap-1">
                    <label class="text-[10px] font-extrabold uppercase text-muted">Project Color</label>
                    <div class="flex items-center gap-2">
                        <input type="color" name="color" id="wtProjectColor" value="{{ old('color', '#3558f4') }}"
                            class="h-11 w-14 flex-none rounded-xl border border-line bg-white p-1"
                            oninput="document.getElementById('wtProjectColorHex').value = this.value">
                        <input id="wtProjectColorHex" value="{{ old('color', '#3558f4') }}" placeholder="#3558f4"
                            oninput="if (/^#[0-9a-fA-F]{6}$/.test(this.value)) document.getElementById('wtProjectColor').value = this.value"
                            class="w-full rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm uppercase">
                    </div>
                </div>
                <div class="grid gap-1">
                    <label class="text-[10px] font-extrabold uppercase text-muted">Project Lead</label>
                    <select name="lead_employee_id" id="wtProjectLead"
                        class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm">
                        <option value="">-</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" @selected((string) old('lead_employee_id') === (string) $employee->id)>{{ $employee->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="grid gap-1">
                <label class="text-[10px] font-extrabold uppercase text-muted">Tracker / Folder Link</label>
                <input name="tracker_url" id="wtProjectTrackerUrl" value="{{ old('tracker_url') }}"
                    placeholder="Google Drive / Sheet / Folder link"
                    class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm">
            </div>
            <div class="grid gap-1">
                <label class="text-[10px] font-extrabold uppercase text-muted">Progress Recap / Project
                    Objective</label>
                <textarea name="progress_recap" id="wtProjectRecap" rows="3"
                    placeholder="Tujuan project, update terakhir, blocker, next milestone"
                    class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm">{{ old('progress_recap') }}</textarea>
            </div>
            <div class="mt-1 flex gap-2">
                <button type="submit" class="btn-wsm-black">Save Project</button>
                <button type="button" @click="open = false"
                    class="rounded-2xl border border-line bg-white px-4 py-2.5 text-[11px] font-extrabold text-ink">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
    // detail = null -> mode tambah; detail = objek project -> mode edit.
    function wtFillProjectForm(project) {
        const form = document.getElementById('wtProjectForm');
        const $ = (id) => document.getElementById(id);
        const isEdit = !!(project && project.id);

        $('wtProjectEyebrow').textContent = isEdit ? 'Edit Project' : 'Add New Project';
        $('wtProjectFormTitle').textContent = isEdit ? 'Edit Project' : 'Create Project';
        form.action = isEdit ?
            `{{ url('/dashboard/work/tracker/proyek') }}/${project.id}` :
            "{{ route('dashboard.work.tracker.projects.store') }}";
        $('wtProjectFormMethod').innerHTML = isEdit ? '<input type="hidden" name="_method" value="PATCH">' : '';
        $('wtProjectId').value = isEdit ? project.id : '';

        const p = project || {};
        const color = p.color || '#3558f4';
        $('wtProjectName').value = p.name || '';
        $('wtProjectStart').value = p.start_date ? p.start_date.substring(0, 10) : '';
        $('wtProjectEnd').value = p.end_date ? p.end_date.substring(0, 10) : '';
        $('wtProjectPriority').value = p.priority || 'Medium';
        $('wtProjectStatus').value = p.status || 'On Development';
        $('wtProjectColor').value = color;
        $('wtProjectColorHex').value = color;
        $('wtProjectLead').value = p.lead_employee_id || '';
        $('wtProjectTrackerUrl').value = p.tracker_url || '';
        $('wtProjectRecap').value = p.progress_recap || '';
    }

    // Validasi gagal waktu mode edit: kembalikan action ke PATCH project yang sama.
    document.addEventListener('DOMContentLoaded', () => {
        const id = document.getElementById('wtProjectId').value;
        if (id) {
            document.getElementById('wtProjectForm').action =
                `{{ url('/dashboard/work/tracker/proyek') }}/${id}`;
            document.getElementById('wtProjectFormMethod').innerHTML =
                '<input type="hidden" name="_method" value="PATCH">';
            document.getElementById('wtProjectEyebrow').textContent = 'Edit Project';
            document.getElementById('wtProjectFormTitle').textContent = 'Edit Project';
        }
    });
</script>
