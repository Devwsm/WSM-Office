{{--
    dashboard/budget/index.blade.php
    ---------------------------------------------------------------------
    Project Budgeting — disamakan konsepnya dengan Work Tracker (2026-10-04):
      1. kartu ringkasan (Project Budget, Budget Allocation, Actual,
         Remaining, Utilization),
      2. filter Project / Kategori / Lagu / Status + Expand/Collapse All,
      3. grafik Budget vs Actual (per kategori / project / lagu),
      4. 3 lapis accordion: Project -> Kategori (warna, urut, akses) -> Item
         (edit satuan langsung di tabel),
      5. unduh PDF, Export/Import Excel (lewat Export & Import Center).

    Istilah: "Project Budget" = anggaran awal project (hanya diedit, di
    halaman sendiri). "Budget Allocation" = jumlah budget semua item
    (dulu berlabel "Total Budget"). Selisih keduanya = belum dialokasikan.

    DATA SENSITIF: Tambah/Edit item, Project Budget, dan Akses kategori
    adalah halaman sendiri (link biasa), bukan modal. Kategori yang
    dibatasi tidak pernah sampai ke view ini untuk yang tidak berhak —
    BudgetReport::cards() sudah membuangnya.
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Project Budgeting', 'navActive' => 'modules'])

@php
    $rp = fn($value) => \App\Models\PayrollRecord::formatRupiah($value);
    $canManage = auth()->user()->canManageModule('budget');
    $pct = fn($value) => $value === null ? '–' : $value . '%';
    $remainingClass = fn($value) => $value < 0 ? 'text-[#a83d35]' : 'text-[#3d7a4d]';
    $plain = fn($value) => rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
@endphp

@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3.5">
        <div>
            <a href="{{ route('dashboard.index') }}" class="text-[11px] font-extrabold text-muted">← Dashboard</a>
            <h2 class="mt-2 text-3xl font-black leading-[0.98] tracking-tight sm:text-[36px]">Project Budgeting</h2>
            <p class="mt-1 text-[13px] text-muted">Budget vs actual per project, dikelompokkan per kategori.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @unless ($report->isEmpty())
                <a href="{{ route('dashboard.budget.pdf', $scope + ['group' => $group]) }}" target="_blank" rel="noopener"
                    class="btn-wsm-white">Unduh PDF</a>
            @endunless
            <a href="{{ route('dashboard.export-import.preview', ['key' => 'budget', 'format' => 'excel'] + array_filter(['project_id' => $selectedProjectId])) }}"
                class="btn-wsm-white">Export Excel</a>
            @if ($canManage)
                <a href="{{ route('dashboard.export-import.import.show', ['key' => 'budget']) }}"
                    class="btn-wsm-white">Import Excel</a>
                {{-- Item wajib masuk project: belum ada project -> jangan buka form, arahkan buat project dulu. --}}
                @if ($projects->isEmpty())
                    <button type="button" onclick="bdNeedProject()" class="btn-wsm-black">+ Tambah Budget</button>
                @else
                    <a href="{{ route('dashboard.budget.create', array_filter(['project_id' => $selectedProjectId])) }}"
                        class="btn-wsm-black">+ Tambah Budget</a>
                @endif
            @endif
        </div>
    </div>

    {{-- 1. Ringkasan --}}
    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-5">
        <div class="card-wsm-white min-w-0">
            <p class="text-[10px] font-black uppercase tracking-wide text-muted">Project Budget</p>
            <p class="mt-1 text-xl font-black tracking-tight sm:text-2xl">{{ $hasAnyPlan ? $rp($planTotal) : '–' }}</p>
            <p class="mt-0.5 text-[10px] text-muted">{{ $hasAnyPlan ? 'anggaran awal project' : 'Belum diisi' }}</p>
        </div>
        <div class="card-wsm-white min-w-0">
            <p class="text-[10px] font-black uppercase tracking-wide text-muted">Budget Allocation</p>
            <p class="mt-1 text-xl font-black tracking-tight sm:text-2xl">{{ $rp($totals['budget']) }}</p>
            <p class="mt-0.5 text-[10px] text-muted">jumlah budget item</p>
        </div>
        <div class="card-wsm-white min-w-0">
            <p class="text-[10px] font-black uppercase tracking-wide text-muted">Actual</p>
            <p class="mt-1 text-xl font-black tracking-tight sm:text-2xl">{{ $rp($totals['actual']) }}</p>
        </div>
        <div class="card-wsm-white min-w-0">
            <p class="text-[10px] font-black uppercase tracking-wide text-muted">Remaining</p>
            <p class="mt-1 text-xl font-black tracking-tight sm:text-2xl {{ $remainingClass($totals['remaining']) }}">
                {{ $rp($totals['remaining']) }}</p>
            @if ($totals['remaining'] < 0)
                <p class="mt-0.5 text-[10px] font-extrabold text-[#a83d35]">Melebihi budget</p>
            @endif
        </div>
        <div class="card-wsm-white min-w-0">
            <p class="text-[10px] font-black uppercase tracking-wide text-muted">Utilization</p>
            <p class="mt-1 text-xl font-black tracking-tight sm:text-2xl {{ ($totals['utilization'] ?? 0) > 100 ? 'text-[#a83d35]' : '' }}">
                {{ $pct($totals['utilization']) }}</p>
            @if ($totals['utilization'] === null)
                <p class="mt-0.5 text-[10px] text-muted">Belum ada budget</p>
            @endif
        </div>
    </div>

    {{-- 2. Filter --}}
    <form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
        <input type="hidden" name="group" value="{{ $group }}">
        @foreach ([['project_id', 'Project', $projects->pluck('name', 'id')->all(), $selectedProjectId, 'Semua Project'], ['category', 'Kategori', collect($categoryOptions)->mapWithKeys(fn($c) => [$c => $c])->all(), $selectedCategory, 'Semua Kategori'], ['song', 'Lagu', collect($songOptions)->mapWithKeys(fn($c) => [$c => $c])->all(), $selectedSong, 'Semua Lagu'], ['status', 'Status', $statusOptions, $selectedStatus, 'Semua Status']] as [$name, $label, $options, $current, $all])
            <label class="grid min-w-34 flex-1 gap-1 sm:flex-none">
                <span class="text-[10px] font-extrabold uppercase text-muted">{{ $label }}</span>
                <select name="{{ $name }}" onchange="this.form.submit()"
                    class="w-full rounded-2xl border border-line bg-white px-3.5 py-2 text-[11px] font-bold text-ink sm:max-w-52">
                    <option value="">{{ $all }}</option>
                    @foreach ($options as $value => $text)
                        <option value="{{ $value }}" @selected(mb_strtolower((string) $current) === mb_strtolower((string) $value))>{{ $text }}</option>
                    @endforeach
                </select>
            </label>
        @endforeach
        @if ($filtering)
            <a href="{{ route('dashboard.budget.index', ['group' => $group]) }}"
                class="rounded-2xl border border-line bg-white px-3.5 py-2 text-[11px] font-extrabold text-ink">Reset</a>
        @endif
        <span class="flex gap-2 sm:ml-auto">
            <button type="button" onclick="bdToggleAll(true)"
                class="rounded-2xl border border-line bg-white px-3.5 py-2 text-[11px] font-extrabold">Expand All</button>
            <button type="button" onclick="bdToggleAll(false)"
                class="rounded-2xl border border-line bg-white px-3.5 py-2 text-[11px] font-extrabold">Collapse All</button>
        </span>
    </form>

    @if (! $report->isEmpty())
        {{-- 3. Grafik Budget vs Actual --}}
        <section class="card-wsm-white mb-5" aria-labelledby="budget-chart-title">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-muted">Budget Comparison</p>
                    <h3 id="budget-chart-title" class="text-lg font-black tracking-tight">Budget vs Actual</h3>
                </div>
                <nav aria-label="Kelompokkan grafik" class="flex flex-wrap gap-1.5">
                    @foreach ($groups as $key => $label)
                        <a href="{{ route('dashboard.budget.index', $scope + ['group' => $key]) }}"
                            @if ($group === $key) aria-current="true" @endif
                            class="rounded-full px-3.5 py-2 text-[11px] font-extrabold transition {{ $group === $key ? 'bg-ink text-white' : 'border border-line bg-white text-ink hover:bg-cream' }}">{{ $label }}</a>
                    @endforeach
                </nav>
            </div>

            <div class="grid gap-3.5">
                @foreach ($chart as $bar)
                    <div class="grid gap-1.5 sm:grid-cols-[minmax(0,170px)_1fr_minmax(0,210px)] sm:items-center sm:gap-3">
                        <div class="truncate text-[11px] font-black" title="{{ $bar['label'] }}">{{ $bar['label'] }}</div>
                        {{-- Batang hanya hiasan; angkanya dibaca dari teks di kanan. --}}
                        <div class="relative h-4.5 overflow-hidden rounded-full bg-[#eee9e0]" aria-hidden="true">
                            <div class="absolute inset-y-0 left-0 rounded-full bg-[#dfe8ff]"
                                style="width: {{ $bar['plan_pct'] }}%; min-width: {{ $bar['budget'] > 0 ? '6px' : '0' }}">
                            </div>
                            <div class="absolute inset-y-1 left-0 rounded-full {{ $bar['over'] ? 'bg-[#d4574c]' : 'bg-brand-blue' }}"
                                style="width: {{ $bar['actual_pct'] }}%; min-width: {{ $bar['actual'] > 0 ? '6px' : '0' }}">
                            </div>
                        </div>
                        <div class="text-[11px] sm:text-right">
                            <strong>{{ $rp($bar['actual']) }}</strong>
                            <span class="text-muted">/ {{ $rp($bar['budget']) }}</span>
                            <span class="ml-1 font-extrabold {{ $bar['over'] ? 'text-[#a83d35]' : 'text-[#3d7a4d]' }}">{{ $pct($bar['utilization']) }}</span>
                            @if ($bar['over'])
                                <span class="badge-wsm-red ml-1">Melebihi budget</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 flex flex-wrap gap-x-4 gap-y-1 text-[10px] font-bold text-muted">
                <span class="inline-flex items-center gap-1.5"><i class="inline-block h-2 w-4 rounded-full bg-[#dfe8ff]"></i> Budget</span>
                <span class="inline-flex items-center gap-1.5"><i class="inline-block h-2 w-4 rounded-full bg-brand-blue"></i> Actual</span>
                <span class="inline-flex items-center gap-1.5"><i class="inline-block h-2 w-4 rounded-full bg-[#d4574c]"></i> Actual melebihi budget</span>
            </div>
        </section>
    @endif

    {{-- 4. Project > Kategori > Item --}}
    <div class="grid gap-4">
        @forelse ($cards as $card)
            @php
                $project = $card['project'];
                $color = \App\Models\Project::colorFor($project);
                $cardKey = 'p' . $project->id;
                $plan = $card['plan'];
                // Belum dialokasikan hanya dihitung kalau semua item project ini terlihat & tidak difilter —
                // kalau ada kategori yang disembunyikan, angkanya akan menyesatkan.
                $canCompare = $plan !== null && ! $card['has_hidden'] && ! $lineFiltering;
                $unallocated = $canCompare ? $plan - $card['budget'] : null;
                $utilBar = min(100, (int) ($card['utilization'] ?? 0));
            @endphp
            <article class="overflow-hidden rounded-3xl border border-line bg-[#fbf8f2]">
                <details class="bd-card group" data-bd-key="{{ $cardKey }}" @if ($selectedProjectId) open @endif>
                    <summary class="flex cursor-pointer list-none flex-col gap-3 p-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0 flex-1">
                            <p class="flex items-center gap-1.5 text-[9px] font-extrabold uppercase tracking-widest text-muted">
                                <span class="h-2.5 w-2.5 flex-none rounded-full" style="background:{{ $color }}"></span>Project
                            </p>
                            <h3 class="mt-0.5 wrap-break-word text-xl font-black leading-tight">{{ $project->name }}</h3>
                            <p class="mt-1 text-xs text-muted">
                                {{ $card['item_count'] }} item · {{ $card['sections']->count() }} kategori
                            </p>
                            <div class="mt-2 flex items-center gap-2.5">
                                <span class="text-[10px] font-bold text-muted">Utilization</span>
                                <div class="h-2 max-w-sm min-w-0 flex-1 overflow-hidden rounded-full bg-[#ece7dd]">
                                    <div class="h-full rounded-full {{ ($card['utilization'] ?? 0) > 100 ? 'bg-[#d4574c]' : '' }}"
                                        style="width:{{ $utilBar }}%;{{ ($card['utilization'] ?? 0) > 100 ? '' : 'background:' . $color }}"></div>
                                </div>
                                <span class="text-sm font-black">{{ $pct($card['utilization']) }}</span>
                            </div>
                            <div class="mt-2.5 flex flex-wrap gap-x-4 gap-y-1 text-[11px]">
                                <span>Project Budget: <strong>{{ $plan !== null ? $rp($plan) : 'Belum diisi' }}</strong></span>
                                <span>Budget Allocation: <strong>{{ $rp($card['budget']) }}</strong></span>
                                <span>Actual: <strong>{{ $rp($card['actual']) }}</strong></span>
                                <span class="{{ $remainingClass($card['remaining']) }}">Variance: <strong>{{ $rp($card['remaining']) }}</strong></span>
                                @if ($unallocated !== null)
                                    <span class="{{ $remainingClass($unallocated) }}">
                                        {{ $unallocated < 0 ? 'Over-allocated' : 'Unallocated' }}: <strong>{{ $rp(abs($unallocated)) }}</strong></span>
                                @endif
                            </div>
                        </div>
                        <div class="flex flex-none flex-wrap items-center gap-1.5 sm:justify-end">
                            @if ($card['remaining'] < 0)
                                <span class="badge-wsm-red">Melebihi budget</span>
                            @endif
                            @if ($unallocated !== null && $unallocated < 0)
                                <span class="badge-wsm-red">Melebihi Project Budget</span>
                            @endif
                            <span class="grid h-8 w-8 place-items-center rounded-xl bg-white text-xs font-black transition group-open:rotate-180">⌄</span>
                        </div>
                    </summary>

                    <div class="border-t border-line">
                        @if ($canManage)
                            <div class="flex flex-wrap items-center gap-2 border-b border-line bg-white/60 px-4 py-2.5">
                                {{-- Halaman sendiri (bukan modal): data anggaran sensitif. --}}
                                <a href="{{ route('dashboard.budget.plan.edit', $project) }}"
                                    class="rounded-2xl border border-line bg-white px-3.5 py-1.5 text-[11px] font-extrabold">
                                    {{ $plan !== null ? 'Edit Project Budget' : 'Isi Project Budget' }}</a>
                                <a href="{{ route('dashboard.budget.create', ['project_id' => $project->id]) }}"
                                    class="rounded-2xl border border-line bg-white px-3.5 py-1.5 text-[11px] font-extrabold">+ Item</a>
                            </div>
                            <form method="POST" action="{{ route('dashboard.budget.categories.store', $project) }}"
                                onsubmit="bdRememberOpen({ p: {{ $project->id }}, s: '' })"
                                class="flex flex-wrap items-center gap-2 border-b border-line bg-white/60 px-4 py-2.5">
                                @csrf
                                <input name="name" required maxlength="100" list="bdCategorySuggestions" autocomplete="off"
                                    placeholder="Kategori baru (mis. Marketing)"
                                    class="min-w-0 flex-1 rounded-2xl border border-line bg-white px-3.5 py-1.5 text-[11px] font-bold sm:max-w-xs sm:flex-none">
                                <button type="submit"
                                    class="rounded-2xl border border-line bg-white px-3.5 py-1.5 text-[11px] font-extrabold">+ Kategori</button>
                            </form>
                        @endif

                        @forelse ($card['sections'] as $section)
                            @php
                                $secColor = $section['color'];
                                $secInk = \App\Models\Project::contrastTextFor($secColor);
                                $cat = $section['model'];
                                $secKey = $cardKey . ':' . md5($section['name']);
                            @endphp
                            <details class="bd-section border-b border-line last:border-b-0" data-bd-key="{{ $secKey }}"
                                data-bd-project="{{ $project->id }}" data-bd-section="{{ $section['name'] }}">
                                <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-3 px-4 py-3"
                                    style="background:{{ $secColor }};color:{{ $secInk }}">
                                    <span class="flex min-w-0 items-center gap-2 wrap-break-word text-[12px] font-black uppercase tracking-wide">
                                        <span class="bd-chevron text-xs transition">⌄</span>{{ $section['name'] }}
                                    </span>
                                    <span class="flex flex-wrap items-center justify-end gap-1.5 text-[#17130a]">
                                        @if (! empty($section['viewer_ids']))
                                            <span title="Hanya orang tertentu yang bisa melihat kategori ini"
                                                class="rounded-full bg-white/70 px-2 py-0.5 text-[10px] font-extrabold">🔒 {{ count($section['viewer_ids']) }} orang</span>
                                        @endif
                                        @if ($canManage)
                                            {{-- Akses per orang: halaman sendiri. --}}
                                            <a href="{{ route('dashboard.budget.categories.access', $cat) }}" onclick="event.stopPropagation()"
                                                title="Atur siapa yang bisa melihat kategori ini"
                                                class="grid h-7 w-7 place-items-center rounded-lg bg-white/70 text-[13px]">👁</a>
                                            {{-- Warna: simpan langsung (PATCH JSON), tanpa reload. --}}
                                            <label title="Ubah warna kategori" onclick="event.stopPropagation()"
                                                class="relative grid h-7 w-7 cursor-pointer place-items-center rounded-lg bg-white/70">
                                                <span class="bd-swatch h-4 w-4 rounded-full border border-black/25" style="background:{{ $secColor }}"></span>
                                                <input type="color" value="{{ $secColor }}" data-orig="{{ $secColor }}"
                                                    onchange="bdSetCategoryColor({{ $cat->id }}, this)"
                                                    aria-label="Warna kategori {{ $section['name'] }}"
                                                    class="absolute inset-0 h-full w-full cursor-pointer opacity-0">
                                            </label>
                                            @unless ($filtering)
                                                @foreach ([['up', '↑', 'Pindah kategori ke atas', $section['is_first']], ['down', '↓', 'Pindah kategori ke bawah', $section['is_last']]] as [$dir, $arrow, $title, $disabled])
                                                    <form method="POST" action="{{ route('dashboard.budget.categories.move', $cat) }}"
                                                        onclick="event.stopPropagation()" onsubmit="bdRememberOpen()">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="direction" value="{{ $dir }}">
                                                        <button type="submit" title="{{ $title }}" @disabled($disabled)
                                                            class="grid h-7 w-7 place-items-center rounded-lg bg-white/70 text-sm font-black disabled:cursor-not-allowed disabled:opacity-35">{{ $arrow }}</button>
                                                    </form>
                                                @endforeach
                                                {{-- Hapus hanya untuk kategori kosong (data keuangan tidak ikut terhapus massal). --}}
                                                @if ($section['rows']->isEmpty())
                                                    <form method="POST" action="{{ route('dashboard.budget.categories.destroy', $cat) }}"
                                                        onclick="event.stopPropagation()"
                                                        data-confirm="Kategori {{ $section['name'] }} masih kosong dan akan dihapus dari project ini."
                                                        data-confirm-title="Hapus kategori?" data-confirm-button="Ya, hapus"
                                                        data-confirm-danger="1">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" title="Hapus kategori"
                                                            class="grid h-7 w-7 place-items-center rounded-lg bg-white/70 text-sm font-black text-[#9b392f]">×</button>
                                                    </form>
                                                @endif
                                            @endunless
                                            <a href="{{ route('dashboard.budget.create', ['project_id' => $project->id, 'category' => $section['name']]) }}"
                                                onclick="event.stopPropagation()" title="Tambah item di kategori ini"
                                                class="grid h-7 w-7 place-items-center rounded-lg bg-white/70 text-sm font-black">+</a>
                                        @endif
                                        <span class="rounded-full bg-white/60 px-2 py-0.5 text-[10px] font-extrabold">{{ $section['rows']->count() }} item</span>
                                        <span class="rounded-full bg-white/60 px-2 py-0.5 text-[10px] font-extrabold">{{ $rp($section['actual']) }} / {{ $rp($section['budget']) }}</span>
                                    </span>
                                </summary>

                                @if ($section['rows']->isEmpty())
                                    <p class="bg-white px-4 py-3 text-[11px] text-muted">Kategori ini masih kosong.
                                        @if ($canManage)
                                            Tambah item lewat tombol <b>+</b> di header.
                                        @endif
                                    </p>
                                @else
                                    <div class="bd-tablewrap overflow-x-auto bg-white">
                                        <table class="bd-table w-full text-left text-[11px]">
                                            <thead>
                                                <tr class="text-[9px] font-extrabold uppercase tracking-widest text-muted">
                                                    <th class="px-3 py-2">Item</th>
                                                    <th class="w-36 px-3 py-2">Lagu</th>
                                                    <th class="w-32 px-3 py-2 text-right">Budget</th>
                                                    <th class="w-32 px-3 py-2 text-right">Actual</th>
                                                    <th class="w-32 px-3 py-2 text-right">Variance</th>
                                                    <th class="w-16 px-3 py-2">Bukti</th>
                                                    <th class="w-52 px-3 py-2">Catatan</th>
                                                    @if ($canManage)
                                                        <th class="w-28 px-3 py-2"><span class="sr-only">Aksi</span></th>
                                                    @endif
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($section['rows'] as $row)
                                                    @php $variance = $row->variance(); @endphp
                                                    <tr class="border-t border-line align-middle">
                                                        <td class="bd-title px-3 py-2.5 font-black leading-snug">
                                                            @if ($canManage)
                                                                <input type="text" value="{{ $row->item }}" data-orig="{{ $row->item }}" maxlength="150"
                                                                    onblur="bdSaveField({{ $row->id }}, 'item', this)"
                                                                    onkeydown="if(event.key==='Enter'){this.blur()}else if(event.key==='Escape'){this.value=this.dataset.orig;this.blur()}"
                                                                    aria-label="Nama item"
                                                                    class="bd-inline w-full rounded-lg border border-transparent bg-transparent px-1.5 py-1 font-black hover:border-line focus:border-line focus:bg-white">
                                                            @else
                                                                {{ $row->item }}
                                                            @endif
                                                        </td>
                                                        <td data-label="Lagu" class="px-3 py-2.5">
                                                            @if ($canManage)
                                                                <input type="text" value="{{ $row->song_title }}" data-orig="{{ $row->song_title }}" maxlength="150"
                                                                    placeholder="–" list="bdSongSuggestions" autocomplete="off"
                                                                    onblur="bdSaveField({{ $row->id }}, 'song_title', this)"
                                                                    onkeydown="if(event.key==='Enter'){this.blur()}else if(event.key==='Escape'){this.value=this.dataset.orig;this.blur()}"
                                                                    aria-label="Lagu"
                                                                    class="bd-inline w-full rounded-lg border border-transparent bg-transparent px-1.5 py-1 text-[11px] hover:border-line focus:border-line focus:bg-white">
                                                            @else
                                                                {{ $row->song_title ?: '-' }}
                                                            @endif
                                                        </td>
                                                        <td data-label="Budget" class="px-3 py-2.5 text-right tabular-nums">
                                                            @if ($canManage)
                                                                <input type="number" min="0" step="any" value="{{ $plain($row->budget) }}" data-orig="{{ $plain($row->budget) }}"
                                                                    data-reload="1" onblur="bdSaveField({{ $row->id }}, 'budget', this)"
                                                                    onkeydown="if(event.key==='Enter'){this.blur()}else if(event.key==='Escape'){this.value=this.dataset.orig;this.blur()}"
                                                                    aria-label="Budget (Rp)"
                                                                    class="bd-inline w-full rounded-lg border border-transparent bg-transparent px-1.5 py-1 text-right text-[11px] tabular-nums hover:border-line focus:border-line focus:bg-white">
                                                            @else
                                                                {{ $rp($row->budget) }}
                                                            @endif
                                                        </td>
                                                        <td data-label="Actual" class="px-3 py-2.5 text-right tabular-nums">
                                                            @if ($canManage)
                                                                <input type="number" min="0" step="any" value="{{ $plain($row->actual) }}" data-orig="{{ $plain($row->actual) }}"
                                                                    data-reload="1" onblur="bdSaveField({{ $row->id }}, 'actual', this)"
                                                                    onkeydown="if(event.key==='Enter'){this.blur()}else if(event.key==='Escape'){this.value=this.dataset.orig;this.blur()}"
                                                                    aria-label="Actual (Rp)"
                                                                    class="bd-inline w-full rounded-lg border border-transparent bg-transparent px-1.5 py-1 text-right text-[11px] tabular-nums hover:border-line focus:border-line focus:bg-white">
                                                            @else
                                                                {{ $rp($row->actual) }}
                                                            @endif
                                                        </td>
                                                        <td data-label="Variance"
                                                            class="px-3 py-2.5 text-right font-extrabold tabular-nums {{ $remainingClass($variance) }}">
                                                            {{ $rp($variance) }}
                                                            @if ($variance < 0)
                                                                <span class="block text-[9px] font-extrabold">Melebihi budget</span>
                                                            @endif
                                                        </td>
                                                        <td data-label="Bukti" class="px-3 py-2.5">
                                                            @if ($row->proof_link)
                                                                <a href="{{ $row->proof_link }}" target="_blank" rel="noopener noreferrer"
                                                                    class="font-extrabold text-[#2647b8] underline">Buka</a>
                                                            @elseif (! $canManage)
                                                                -
                                                            @endif
                                                            @if ($canManage)
                                                                <button type="button" title="{{ $row->proof_link ? 'Ubah link bukti' : 'Tambah link bukti' }}"
                                                                    onclick="bdEditLink({{ $row->id }}, this)" data-link="{{ $row->proof_link }}"
                                                                    class="ml-1 rounded-md bg-[#ece7dd] px-1.5 py-0.5 text-[9px] font-extrabold">{{ $row->proof_link ? '✎' : '+ Link' }}</button>
                                                            @endif
                                                        </td>
                                                        <td data-label="Catatan" class="bd-note px-3 py-2.5">
                                                            @if ($canManage)
                                                                <textarea rows="1" placeholder="Tambah catatan..." data-orig="{{ $row->note }}"
                                                                    onblur="bdSaveField({{ $row->id }}, 'note', this)"
                                                                    class="bd-note-input block w-full resize-y rounded-lg border border-transparent bg-transparent px-1.5 py-1.5 text-[11px] leading-snug hover:border-line focus:border-line focus:bg-white">{{ $row->note }}</textarea>
                                                            @else
                                                                {{ $row->note ?: '-' }}
                                                            @endif
                                                        </td>
                                                        @if ($canManage)
                                                            <td class="bd-actions px-3 py-2.5">
                                                                <div class="flex gap-1">
                                                                    {{-- Edit lengkap = halaman sendiri, bukan modal. --}}
                                                                    <a href="{{ route('dashboard.budget.edit', $row) }}"
                                                                        class="rounded-lg bg-[#ece7dd] px-2 py-1 text-[9px] font-extrabold">Edit</a>
                                                                    <form method="POST" action="{{ route('dashboard.budget.destroy', $row) }}"
                                                                        data-confirm="{{ $row->item }} akan dihapus permanen."
                                                                        data-confirm-title="Hapus baris budget ini?"
                                                                        data-confirm-button="Ya, hapus" data-confirm-danger="1">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="submit"
                                                                            class="rounded-lg bg-[#ffded8] px-2 py-1 text-[9px] font-extrabold text-[#9b392f]">Hapus</button>
                                                                    </form>
                                                                </div>
                                                            </td>
                                                        @endif
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </details>
                        @empty
                            <p class="p-4 text-xs text-muted">
                                {{ $lineFiltering ? 'Belum ada item pada filter ini.' : 'Project ini belum punya kategori / item budget.' }}
                                @if ($canManage && ! $lineFiltering)
                                    <a href="{{ route('dashboard.budget.create', ['project_id' => $project->id]) }}"
                                        class="ml-1 font-extrabold text-ink underline">Tambah item pertama</a>
                                @endif
                            </p>
                        @endforelse
                    </div>
                </details>
            </article>
        @empty
            <p class="rounded-3xl border border-dashed border-line p-6 text-sm text-muted">
                @if ($projects->isEmpty())
                    Belum ada project. Buat dulu di menu <a href="{{ route('dashboard.work.projects.index') }}" class="font-extrabold underline">Projects</a>.
                @else
                    Belum ada baris budget yang cocok dengan filter ini.
                @endif
            </p>
        @endforelse
    </div>

    <datalist id="bdCategorySuggestions">
        @foreach (\App\Support\BudgetReport::categorySuggestions(auth()->user()) as $suggestion)
            <option value="{{ $suggestion }}"></option>
        @endforeach
    </datalist>
    <datalist id="bdSongSuggestions">
        @foreach (\App\Support\BudgetReport::songSuggestions(auth()->user()) as $suggestion)
            <option value="{{ $suggestion }}"></option>
        @endforeach
    </datalist>

    <style>
        details.bd-section[open]>summary .bd-chevron {
            transform: rotate(180deg);
        }

        details>summary::-webkit-details-marker {
            display: none;
        }

        /* Item di dalam kategori: responsif mengikuti LEBAR AREA KONTEN (container query), bukan lebar layar —
           sidebar dashboard makan ~260px. Di bawah 1000px tiap item jadi kartu 4 kolom, di bawah 640px 2 kolom. */
        .bd-tablewrap {
            container-type: inline-size;
        }

        .bd-note-input {
            min-height: 2rem;
            field-sizing: content;
        }

        @container (max-width: 1000px) {
            .bd-table thead {
                display: none;
            }

            .bd-table,
            .bd-table tbody {
                display: block;
                width: 100%;
            }

            .bd-table tr {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 4px 12px;
                padding: 12px 6px;
            }

            .bd-table td {
                display: block;
                min-width: 0;
                padding: 4px 8px !important;
            }

            .bd-table td[data-label]::before {
                content: attr(data-label);
                display: block;
                margin-bottom: 2px;
                font-size: 9px;
                font-weight: 800;
                letter-spacing: .08em;
                text-transform: uppercase;
                color: #8b867e;
            }

            .bd-table .bd-title,
            .bd-table .bd-actions {
                grid-column: 1 / -1;
            }

            .bd-table .bd-title {
                font-size: 13px;
            }

            .bd-table .bd-note {
                grid-column: span 3;
            }
        }

        @container (max-width: 640px) {
            .bd-table tr {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .bd-table .bd-note {
                grid-column: 1 / -1;
            }
        }
    </style>

    <script>
        const bdCsrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '{{ csrf_token() }}';
        const bdBase = "{{ url('/dashboard/budget') }}";
        const bdHeaders = () => ({
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': bdCsrf(),
            'Accept': 'application/json'
        });

        function bdError(message) {
            if (window.WsmAlert) WsmAlert.error(message);
            else alert(message);
        }

        // --- Edit satuan: simpan saat blur kalau isinya berubah. Budget/Actual memuat ulang halaman
        // (ringkasan, variance, grafik ikut segar); state buka tutup dipertahankan. ---
        function bdSaveField(id, field, el) {
            const orig = el.dataset.orig ?? '';
            if (el.value === orig) return;
            el.classList.add('opacity-60');
            fetch(`${bdBase}/${id}/field`, {
                method: 'PATCH',
                headers: bdHeaders(),
                body: JSON.stringify({
                    field,
                    value: el.value
                }),
            }).then(async (res) => {
                if (!res.ok) {
                    const body = await res.json().catch(() => ({}));
                    throw new Error(body.errors ? Object.values(body.errors).flat()[0] : (body.message || ''));
                }
                el.dataset.orig = el.value;
                if (el.dataset.reload === '1') {
                    bdRememberOpen();
                    window.location.reload();
                    return;
                }
                el.classList.remove('opacity-60');
                el.classList.add('border-[#27c84d]');
                setTimeout(() => el.classList.remove('border-[#27c84d]'), 900);
            }).catch((e) => {
                el.classList.remove('opacity-60');
                el.value = orig;
                bdError(e.message || 'Gagal menyimpan perubahan, coba lagi.');
            });
        }

        // Link bukti bayar: dialog SweetAlert (WsmAlert.prompt). Isian kosong = hapus link.
        async function bdEditLink(id, btn) {
            const current = btn.dataset.link || '';
            let next;

            if (window.WsmAlert && WsmAlert.prompt) {
                const result = await WsmAlert.prompt({
                    title: current ? 'Ubah link bukti bayar' : 'Tambah link bukti bayar',
                    text: 'Tempel link Google Drive bukti pembayaran. Kosongkan lalu simpan untuk menghapus link.',
                    value: current,
                    placeholder: 'https://...',
                    inputType: 'url',
                    validate: (v) => (v.trim() === '' || /^https?:\/\/\S+$/i.test(v.trim())) ? undefined :
                        'Link harus diawali http:// atau https://',
                });
                if (!result.isConfirmed) return;
                next = (result.value || '').trim();
            } else {
                const typed = window.prompt('Link bukti bayar (kosongkan untuk menghapus):', current);
                if (typed === null) return;
                next = typed.trim();
            }

            if (next === current) return;
            fetch(`${bdBase}/${id}/field`, {
                method: 'PATCH',
                headers: bdHeaders(),
                body: JSON.stringify({
                    field: 'proof_link',
                    value: next
                }),
            }).then((res) => {
                if (!res.ok) throw new Error();
                bdRememberOpen();
                window.location.reload();
            }).catch(() => bdError('Link gagal disimpan. Pastikan diawali http:// atau https:// (maks 500 karakter).'));
        }

        // --- Warna kategori: simpan langsung, header diwarnai ulang tanpa reload ---
        function bdInkFor(hex) {
            const n = parseInt(hex.slice(1), 16);
            const lum = (0.299 * (n >> 16) + 0.587 * ((n >> 8) & 255) + 0.114 * (n & 255)) / 255;
            return lum > 0.6 ? '#17130a' : '#ffffff';
        }

        function bdSetCategoryColor(id, input) {
            const color = input.value;
            fetch(`${bdBase}/kategori/${id}`, {
                method: 'PATCH',
                headers: bdHeaders(),
                body: JSON.stringify({
                    color
                }),
            }).then((res) => {
                if (!res.ok) throw new Error();
                const summary = input.closest('summary');
                summary.style.background = color;
                summary.style.color = bdInkFor(color);
                input.parentElement.querySelector('.bd-swatch').style.background = color;
                input.dataset.orig = color;
            }).catch(() => {
                input.value = input.dataset.orig;
                bdError('Warna kategori gagal disimpan, coba lagi.');
            });
        }

        // Belum ada project -> form item tidak dibuka; arahkan buat project dulu.
        function bdNeedProject() {
            const go = () => window.location.href = "{{ route('dashboard.work.projects.index') }}";
            if (window.WsmAlert) {
                WsmAlert.confirm({
                    title: 'Belum ada project',
                    text: 'Budget harus masuk ke sebuah project. Buat project dulu di menu Projects.',
                    confirmText: 'Buat Project',
                    cancelText: 'Nanti',
                    icon: 'info',
                }).then((r) => r.isConfirmed && go());
            } else if (confirm('Belum ada project. Buat project dulu di menu Projects?')) {
                go();
            }
        }

        // --- Buka/tutup semua. Default SEMUA TERTUTUP tiap halaman dibuka. ---
        function bdToggleAll(open) {
            document.querySelectorAll('details[data-bd-key]').forEach((d) => d.open = open);
        }

        // Aksi yang me-reload halaman tidak boleh menutup ulang semuanya: state buka disimpan
        // sebentar di sessionStorage, dipakai sekali saat halaman berikutnya dimuat (kedaluwarsa 15 detik).
        function bdRememberOpen(target) {
            try {
                sessionStorage.setItem('bd-restore', JSON.stringify({
                    t: Date.now(),
                    keys: [...document.querySelectorAll('details[data-bd-key][open]')].map((d) => d.dataset.bdKey),
                    target: target || null,
                }));
            } catch (e) {}
        }
        (function bdRestoreOpen() {
            let saved = null;
            try {
                saved = JSON.parse(sessionStorage.getItem('bd-restore'));
                sessionStorage.removeItem('bd-restore');
            } catch (e) {}
            if (!saved || Date.now() - saved.t > 15000) return;
            document.querySelectorAll('details[data-bd-key]').forEach((d) => {
                if (saved.keys.includes(d.dataset.bdKey)) d.open = true;
            });
            if (saved.target) {
                const card = document.querySelector(`details[data-bd-key="p${saved.target.p}"]`);
                if (card) card.open = true;
            }
        })();
        // Form hapus: ingat state hanya setelah dikonfirmasi (submit kedua dari alerts.js), bukan saat dibatalkan.
        document.querySelectorAll('form[data-confirm]').forEach((f) => f.addEventListener('submit', () => {
            if (f.dataset.confirmed === '1') bdRememberOpen();
        }));

        // 3 lapis: menutup project ikut menutup semua kategori di dalamnya.
        document.querySelectorAll('details.bd-card').forEach((card) => card.addEventListener('toggle', () => {
            if (!card.open) card.querySelectorAll('details.bd-section').forEach((s) => s.open = false);
        }));
    </script>
@endsection
