{{--
    dashboard/work/tracker/index.blade.php
    ---------------------------------------------------------------------
    Fase 9 lanjutan (audit ronde 6, 2026-09-09) — board kanban Work
    Tracker sisi admin. Kolom = WorkItem::PROGRESS_OPTIONS, drag-drop
    antar kolom ganti `progress` lewat fetch PATCH ke
    dashboard.work.tracker.items.progress (lihat WorkTrackerController).

    SENGAJA beda dari `trackerBoardMarkup()` prototype (grouped-list per
    Project → Section, BUKAN kanban drag-drop) — keputusan eksplisit
    user pas ditanya (lihat README), bukan salah audit. Style warna
    kolom & badge dipakai ulang dari `employee/_work-item-card.blade.php`
    (badge-wsm-*, focus color map) biar konsisten satu sistem, bukan
    devain baru.

    Reload penuh setelah tiap aksi (drag/CRUD) — SENGAJA, bukan lupa
    optimistic-UI: codebase ini pola-nya server-rendered + `back()`
    di semua tempat lain (Home, Riwayat, dst), jadi board ini ngikutin
    biar konsisten, bukan satu-satunya halaman yang butuh state JS
    kompleks buat sinkron ulang count/filter.
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Work Tracker', 'navActive' => 'modules'])

@section('content')
    <div x-data="{ taskModalOpen: false, projectModalOpen: false, editingProject: null }" @wt-open-task-modal.window="taskModalOpen = true">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-3.5">
            <div>
                <a href="{{ route('dashboard.work.index') }}" class="text-[11px] font-extrabold text-muted">← Work
                    Control</a>
                <h2 class="mt-2 text-[36px] font-black leading-[0.98] tracking-tight">Work Tracker</h2>
                <p class="mt-1 text-[13px] text-muted">Board kanban semua task lintas Project & PIC.</p>
            </div>
            @if (auth()->user()->canManageModule('work'))
                <div class="flex gap-2">
                    <button type="button" onclick="wtResetProjectForm()"
                        @click="editingProject = null; projectModalOpen = true"
                        class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-[11px] font-extrabold text-ink">
                        Kelola Projects
                    </button>
                    <button type="button" onclick="wtResetTaskForm()" @click="taskModalOpen = true" class="btn-wsm-black">+
                        Tambah Task</button>
                </div>
            @endif
        </div>

        <div class="mb-5 flex gap-2">
            <a href="{{ route('dashboard.work.index') }}"
                class="rounded-2xl border border-line bg-white px-3.5 py-2 text-[11px] font-extrabold text-ink">MoM
                &amp; Memo</a>
            <span class="rounded-2xl bg-ink px-3.5 py-2 text-[11px] font-extrabold text-white">Work Tracker</span>
            <a href="{{ route('dashboard.work.meetings.index') }}"
                class="rounded-2xl border border-line bg-white px-3.5 py-2 text-[11px] font-extrabold text-ink">Rapat
                &amp; Action Item</a>
        </div>

        {{-- Filter Project --}}
        <form method="GET" class="mb-4 flex flex-wrap items-center gap-2">
            <select name="project_id" onchange="this.form.submit()"
                class="rounded-2xl border border-line bg-white px-3.5 py-2 text-[11px] font-bold text-ink">
                <option value="">Semua Project</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}" @selected($selectedProjectId === $project->id)>
                        {{ $project->name }}
                    </option>
                @endforeach
            </select>
        </form>

        {{-- Board --}}
        <div class="-mx-4 overflow-x-auto px-4 pb-3">
            <div class="flex gap-3.5" style="min-width:max-content">
                @foreach ($columns as $status => $items)
                    @php
                        $colColor = match ($status) {
                            'Done' => 'badge-wsm-green',
                            'Follow Up' => 'badge-wsm-yellow',
                            'On Development' => 'badge-wsm-blue',
                            default => 'badge-wsm-gray',
                        };
                    @endphp
                    <div class="w-67.5 flex-none rounded-2xl bg-[#f2f0eb] p-2.5" data-column="{{ $status }}"
                        ondragover="event.preventDefault()" ondrop="wtHandleDrop(event, '{{ $status }}')">
                        <div class="mb-2 flex items-center justify-between px-1">
                            <span class="{{ $colColor }}">{{ $status }}</span>
                            <span class="text-[10px] font-extrabold text-muted">{{ $items->count() }}</span>
                        </div>
                        <div class="grid gap-2">
                            @forelse ($items as $item)
                                @php
                                    $focus = $item->computedFocus();
                                    $focusColors = match ($focus) {
                                        'HARI INI' => ['bg' => '#ffe876', 'text' => '#392f00'],
                                        'BESOK' => ['bg' => '#fff0ae', 'text' => '#604d00'],
                                        'MINGGU INI', 'MINGGU DEPAN' => ['bg' => '#e8f0ff', 'text' => '#3158a8'],
                                        'AMAN', 'NOT URGENT' => ['bg' => '#e6f4e9', 'text' => '#1c6c39'],
                                        'KELEWAT' => ['bg' => '#ffded8', 'text' => '#9b392f'],
                                        'SELESAI' => ['bg' => '#ccebd5', 'text' => '#176c37'],
                                        default => ['bg' => '#eeeae3', 'text' => '#625c54'],
                                    };
                                @endphp
                                <article draggable="{{ auth()->user()->canManageModule('work') ? 'true' : 'false' }}"
                                    ondragstart="event.dataTransfer.setData('text/plain', '{{ $item->id }}')"
                                    class="cursor-grab rounded-xl border border-line bg-white p-3 active:cursor-grabbing"
                                    x-data="{ open: false }">
                                    <p class="truncate text-[9px] font-extrabold uppercase tracking-wide text-muted">
                                        {{ $item->project?->name ?? 'Tanpa Project' }}
                                        @if ($item->section)
                                            · {{ $item->section }}
                                        @endif
                                    </p>
                                    <h4 class="mt-0.5 text-[12px] font-black leading-snug">{{ $item->title }}</h4>
                                    <div class="mt-1.5 flex flex-wrap items-center gap-1">
                                        <span class="inline-flex rounded-full px-1.5 py-0.5 text-[7px] font-black"
                                            style="background:{{ $focusColors['bg'] }};color:{{ $focusColors['text'] }}">
                                            {{ $focus }}
                                        </span>
                                        <span
                                            class="rounded-full bg-[#f2f0eb] px-1.5 py-0.5 text-[8px] font-bold text-[#5e5952]">
                                            {{ $item->due_date ? $item->due_date->translatedFormat('d M') : 'No date' }}
                                        </span>
                                    </div>
                                    <div class="mt-1.5 flex items-center justify-between">
                                        <span class="text-[9px] font-bold text-muted">
                                            {{ $item->pic?->name ?? 'Belum di-assign' }}
                                        </span>
                                        @if (auth()->user()->canManageModule('work'))
                                            <button type="button" @click="open = !open"
                                                class="text-[10px] font-black text-muted">⋯</button>
                                        @endif
                                    </div>
                                    @if (auth()->user()->canManageModule('work'))
                                        <div x-show="open" x-cloak class="mt-2 flex gap-1.5 border-t border-line pt-2">
                                            <button type="button" data-item="{{ $item->toJson() }}"
                                                onclick="wtOpenEditTask(JSON.parse(this.dataset.item))"
                                                class="rounded-lg bg-[#ece7dd] px-2 py-1 text-[9px] font-extrabold">Edit</button>
                                            <form method="POST"
                                                action="{{ route('dashboard.work.tracker.items.destroy', $item) }}"
                                                data-confirm="Hapus task &quot;{{ $item->title }}&quot;?"
                                                data-confirm-title="Hapus task?" data-confirm-button="Ya, hapus">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="rounded-lg bg-[#ffded8] px-2 py-1 text-[9px] font-extrabold text-[#9b392f]">Hapus</button>
                                            </form>
                                        </div>
                                    @endif
                                </article>
                            @empty
                                <p class="px-1 text-[10px] text-muted">Kosong.</p>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Modal Tambah/Edit Task --}}
        <div x-show="taskModalOpen" x-cloak
            class="fixed inset-0 z-50 grid place-items-end bg-black/40 p-0 sm:place-items-center sm:p-4">
            <div @click.outside="taskModalOpen = false"
                class="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-t-4xl bg-cream p-5 sm:rounded-4xl">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-lg font-black" id="wtTaskFormTitle">Tambah Task</h3>
                    <button type="button" @click="taskModalOpen = false"
                        class="grid h-9 w-9 place-items-center rounded-2xl bg-[#ece7dd]">✕</button>
                </div>
                <form id="wtTaskForm" method="POST" action="{{ route('dashboard.work.tracker.items.store') }}"
                    class="grid gap-3">
                    @csrf
                    <span id="wtTaskFormMethod"></span>
                    <div class="grid gap-1">
                        <label class="text-[10px] font-extrabold uppercase text-muted">Judul Task</label>
                        <input name="title" id="wtTaskTitle" required
                            class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="grid gap-1">
                            <label class="text-[10px] font-extrabold uppercase text-muted">Project</label>
                            <select name="project_id" id="wtTaskProject"
                                class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm">
                                <option value="">Tanpa Project</option>
                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid gap-1">
                            <label class="text-[10px] font-extrabold uppercase text-muted">Section</label>
                            <input name="section" id="wtTaskSection" list="wtSectionSuggestions"
                                class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm">
                            <datalist id="wtSectionSuggestions">
                                @foreach (\App\Models\WorkItem::SECTION_SUGGESTIONS as $section)
                                    <option value="{{ $section }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="grid gap-1">
                            <label class="text-[10px] font-extrabold uppercase text-muted">PIC</label>
                            <select name="pic_employee_id" id="wtTaskPic"
                                class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm">
                                <option value="">Belum di-assign</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid gap-1">
                            <label class="text-[10px] font-extrabold uppercase text-muted">Due Date</label>
                            <input type="date" name="due_date" id="wtTaskDue"
                                class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="grid gap-1">
                            <label class="text-[10px] font-extrabold uppercase text-muted">Progress</label>
                            <select name="progress" id="wtTaskProgress"
                                class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm">
                                @foreach (\App\Models\WorkItem::PROGRESS_OPTIONS as $status)
                                    <option value="{{ $status }}">{{ $status }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid gap-1">
                            <label class="text-[10px] font-extrabold uppercase text-muted">Priority</label>
                            <select name="priority" id="wtTaskPriority"
                                class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm">
                                @foreach (\App\Models\WorkItem::PRIORITIES as $priority)
                                    <option value="{{ $priority }}">{{ $priority }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="grid gap-1">
                        <label class="text-[10px] font-extrabold uppercase text-muted">Link (opsional)</label>
                        <input name="link" id="wtTaskLink" placeholder="https://..."
                            class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm">
                    </div>
                    <div class="grid gap-1">
                        <label class="text-[10px] font-extrabold uppercase text-muted">Notes</label>
                        <textarea name="notes" id="wtTaskNotes" rows="2"
                            class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm"></textarea>
                    </div>
                    <button type="submit" class="btn-wsm-black mt-1">Simpan Task</button>
                </form>
            </div>
        </div>

        {{-- Modal Kelola Projects --}}
        <div x-show="projectModalOpen" x-cloak
            class="fixed inset-0 z-50 grid place-items-end bg-black/40 p-0 sm:place-items-center sm:p-4">
            <div @click.outside="projectModalOpen = false"
                class="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-t-4xl bg-cream p-5 sm:rounded-4xl">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-lg font-black">Kelola Projects</h3>
                    <button type="button" @click="projectModalOpen = false"
                        class="grid h-9 w-9 place-items-center rounded-2xl bg-[#ece7dd]">✕</button>
                </div>

                <div class="mb-4 grid gap-2">
                    @forelse ($projects as $project)
                        <div class="flex items-center justify-between rounded-2xl border border-line bg-white p-3">
                            <div class="min-w-0">
                                <p class="truncate text-xs font-black">{{ $project->name }}</p>
                                <p class="text-[10px] text-muted">{{ $project->status }} ·
                                    {{ $project->priority }}</p>
                            </div>
                            <div class="flex flex-none gap-1.5">
                                <button type="button" data-project="{{ $project->toJson() }}"
                                    onclick="wtOpenEditProject(JSON.parse(this.dataset.project))"
                                    class="rounded-lg bg-[#ece7dd] px-2 py-1 text-[9px] font-extrabold">Edit</button>
                                <form method="POST"
                                    action="{{ route('dashboard.work.tracker.projects.destroy', $project) }}"
                                    data-confirm="Hapus project &quot;{{ $project->name }}&quot;? Task yang nempel akan dipindah jadi Tanpa Project, bukan ikut kehapus."
                                    data-confirm-title="Hapus project?" data-confirm-button="Ya, hapus">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="rounded-lg bg-[#ffded8] px-2 py-1 text-[9px] font-extrabold text-[#9b392f]">Hapus</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-muted">Belum ada project.</p>
                    @endforelse
                </div>

                <h4 class="mb-2 text-xs font-black" id="wtProjectFormTitle">Tambah Project</h4>
                <form id="wtProjectForm" method="POST" action="{{ route('dashboard.work.tracker.projects.store') }}"
                    class="grid gap-3">
                    @csrf
                    <span id="wtProjectFormMethod"></span>
                    <div class="grid gap-1">
                        <label class="text-[10px] font-extrabold uppercase text-muted">Nama Project</label>
                        <input name="name" id="wtProjectName" required
                            class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="grid gap-1">
                            <label class="text-[10px] font-extrabold uppercase text-muted">Mulai</label>
                            <input type="date" name="start_date" id="wtProjectStart"
                                class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm">
                        </div>
                        <div class="grid gap-1">
                            <label class="text-[10px] font-extrabold uppercase text-muted">Selesai</label>
                            <input type="date" name="end_date" id="wtProjectEnd"
                                class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="grid gap-1">
                            <label class="text-[10px] font-extrabold uppercase text-muted">Priority</label>
                            <select name="priority" id="wtProjectPriority"
                                class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm">
                                @foreach (\App\Models\Project::PRIORITIES as $priority)
                                    <option value="{{ $priority }}">{{ $priority }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid gap-1">
                            <label class="text-[10px] font-extrabold uppercase text-muted">Status</label>
                            <select name="status" id="wtProjectStatus"
                                class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm">
                                @foreach (\App\Models\Project::STATUSES as $status)
                                    <option value="{{ $status }}">{{ $status }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="grid gap-1">
                        <label class="text-[10px] font-extrabold uppercase text-muted">Lead</label>
                        <select name="lead_employee_id" id="wtProjectLead"
                            class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm">
                            <option value="">-</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid gap-1">
                        <label class="text-[10px] font-extrabold uppercase text-muted">Tracker URL (opsional)</label>
                        <input name="tracker_url" id="wtProjectTrackerUrl" placeholder="https://..."
                            class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm">
                    </div>
                    <div class="grid gap-1">
                        <label class="text-[10px] font-extrabold uppercase text-muted">Progress Recap
                            (opsional)</label>
                        <textarea name="progress_recap" id="wtProjectRecap" rows="2"
                            class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm"></textarea>
                    </div>
                    <button type="submit" class="btn-wsm-black mt-1">Simpan Project</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // --- Drag-drop antar kolom (native HTML5 DnD, bukan library) ---
        function wtHandleDrop(event, newStatus) {
            event.preventDefault();
            const itemId = event.dataTransfer.getData('text/plain');
            if (!itemId) return;

            fetch(`/dashboard/work/tracker/task/${itemId}/progress`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ??
                        '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    progress: newStatus
                }),
            }).then((res) => {
                if (!res.ok) throw new Error('gagal update progress');
                window.location.reload();
            }).catch(() => alert('Gagal update progress, coba lagi.'));
        }

        // --- Form Task: satu form dipakai buat Tambah & Edit, dibedain
        // lewat action URL + hidden _method (POST create vs PATCH update). ---
        function wtResetTaskForm() {
            document.getElementById('wtTaskFormTitle').textContent = 'Tambah Task';
            const form = document.getElementById('wtTaskForm');
            form.action = "{{ route('dashboard.work.tracker.items.store') }}";
            document.getElementById('wtTaskFormMethod').innerHTML = '';
            form.reset();
        }

        function wtOpenEditTask(item) {
            document.getElementById('wtTaskFormTitle').textContent = 'Edit Task';
            const form = document.getElementById('wtTaskForm');
            form.action = `/dashboard/work/tracker/task/${item.id}`;
            document.getElementById('wtTaskFormMethod').innerHTML = '<input type="hidden" name="_method" value="PATCH">';
            document.getElementById('wtTaskTitle').value = item.title || '';
            document.getElementById('wtTaskProject').value = item.project_id || '';
            document.getElementById('wtTaskSection').value = item.section || '';
            document.getElementById('wtTaskPic').value = item.pic_employee_id || '';
            document.getElementById('wtTaskDue').value = item.due_date ? item.due_date.substring(0, 10) : '';
            document.getElementById('wtTaskProgress').value = item.progress || 'Pending';
            document.getElementById('wtTaskPriority').value = item.priority || 'Medium';
            document.getElementById('wtTaskLink').value = item.link || '';
            document.getElementById('wtTaskNotes').value = item.notes || '';
            window.dispatchEvent(new CustomEvent('wt-open-task-modal'));
        }

        // --- Form Project: sama pola kayak Task di atas. ---
        function wtResetProjectForm() {
            document.getElementById('wtProjectFormTitle').textContent = 'Tambah Project';
            const form = document.getElementById('wtProjectForm');
            form.action = "{{ route('dashboard.work.tracker.projects.store') }}";
            document.getElementById('wtProjectFormMethod').innerHTML = '';
            form.reset();
        }

        function wtOpenEditProject(project) {
            document.getElementById('wtProjectFormTitle').textContent = 'Edit Project';
            const form = document.getElementById('wtProjectForm');
            form.action = `/dashboard/work/tracker/proyek/${project.id}`;
            document.getElementById('wtProjectFormMethod').innerHTML = '<input type="hidden" name="_method" value="PATCH">';
            document.getElementById('wtProjectName').value = project.name || '';
            document.getElementById('wtProjectStart').value = project.start_date ? project.start_date.substring(0, 10) : '';
            document.getElementById('wtProjectEnd').value = project.end_date ? project.end_date.substring(0, 10) : '';
            document.getElementById('wtProjectPriority').value = project.priority || 'Medium';
            document.getElementById('wtProjectStatus').value = project.status || 'Pending';
            document.getElementById('wtProjectLead').value = project.lead_employee_id || '';
            document.getElementById('wtProjectTrackerUrl').value = project.tracker_url || '';
            document.getElementById('wtProjectRecap').value = project.progress_recap || '';
        }
    </script>
@endsection
