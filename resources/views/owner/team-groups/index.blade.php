{{--
    owner/team-groups/index.blade.php
    ---------------------------------------------------------------------
    2026-10-02 — padanan "Group Team Manager" prototype. Satu form dipakai
    untuk buat & edit (Alpine mengisi ulang field saat tombol Edit ditekan),
    daftar kelompok di bawahnya.
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Kelompok Tim', 'navActive' => 'employees'])

@section('content')
    @php
        $groupsData = $groups
            ->mapWithKeys(
                fn($g) => [
                    $g->id => [
                        'id' => $g->id,
                        'name' => $g->name,
                        'color' => $g->color,
                        'members' => $g->members->pluck('id')->all(),
                    ],
                ],
            )
            ->all();
    @endphp

    <div class="mb-6">
        <a href="{{ route('owner.employees.index') }}" class="text-[11px] font-extrabold text-muted">← Karyawan</a>
        <h2 class="mt-2 text-[36px] font-black leading-[0.98] tracking-tight">Kelompok Tim</h2>
        <p class="mt-1 text-[13px] text-muted">Kelompok custom untuk membatasi visibility project. Satu karyawan boleh masuk
            beberapa kelompok.</p>
    </div>

    <div class="grid gap-5 lg:grid-cols-[1fr_1.1fr]" x-data="{
        groups: @js($groupsData),
        editingId: @js(old('_editing') ?: null),
        name: @js(old('name', '')),
        color: @js(old('color', \App\Models\TeamGroup::DEFAULT_COLOR)),
        members: @js(array_map('intval', old('member_ids', []))),
        edit(id) {
            const g = this.groups[id];
            this.editingId = id;
            this.name = g.name;
            this.color = g.color;
            this.members = [...g.members];
            this.$nextTick(() => this.$refs.name.focus());
        },
        reset() { this.editingId = null;
            this.name = '';
            this.color = '{{ \App\Models\TeamGroup::DEFAULT_COLOR }}';
            this.members = []; },
    }">
        <form method="POST"
            :action="editingId ? '{{ url('owner/kelompok-tim') }}/' + editingId : '{{ route('owner.team-groups.store') }}'"
            class="card-wsm-white grid h-fit gap-3.5">
            @csrf
            <template x-if="editingId">
                <div>
                    <input type="hidden" name="_method" value="PATCH">
                    <input type="hidden" name="_editing" :value="editingId">
                </div>
            </template>

            <p class="text-sm font-extrabold" x-text="editingId ? 'Edit Kelompok' : 'Kelompok Baru'"></p>

            <div>
                <label class="field-label-wsm">Nama Kelompok</label>
                <input type="text" name="name" x-model="name" x-ref="name" maxlength="60"
                    placeholder="Contoh: WS TEAM" class="input-wsm mt-1.5" required>
                @error('name')
                    <p class="mt-1 text-[11px] font-extrabold text-[#a83d35]">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="field-label-wsm">Warna</label>
                <div class="mt-1.5 flex items-center gap-2">
                    <input type="color" name="color" x-model="color"
                        class="h-10 w-14 cursor-pointer rounded-lg border border-line bg-white p-1">
                    <span class="text-xs text-muted" x-text="color"></span>
                </div>
            </div>
            <div>
                <label class="field-label-wsm">Anggota</label>
                <div class="mt-1.5 grid max-h-72 gap-1.5 overflow-y-auto rounded-wsm border border-line bg-[#faf8f3] p-2.5">
                    @foreach ($employees as $emp)
                        <label class="flex items-center gap-2.5 rounded-xl px-2 py-1.5 text-sm hover:bg-white">
                            <input type="checkbox" name="member_ids[]" value="{{ $emp->id }}" x-model.number="members"
                                class="h-4 w-4">
                            <span class="min-w-0">
                                <strong class="block truncate text-[13px]">{{ $emp->name }}</strong>
                                <span
                                    class="block truncate text-[10px] text-muted">{{ $emp->job_title ?: $emp->division ?: '—' }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                <p class="mt-1 text-[11px] text-muted"><span x-text="members.length"></span> anggota dipilih</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="submit" class="btn-wsm-black"
                    x-text="editingId ? 'Simpan Perubahan' : 'Simpan Kelompok'"></button>
                <button type="button" x-show="editingId" x-cloak @click="reset()" class="btn-wsm-white">Batal Edit</button>
            </div>
        </form>

        <div class="grid h-fit gap-3">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-black">Daftar Kelompok</h3>
                <span
                    class="rounded-full bg-[#ece7dd] px-3 py-1 text-[10px] font-extrabold text-muted">{{ $groups->count() }}
                    kelompok</span>
            </div>

            @forelse ($groups as $group)
                @php $used = $group->projectsUsingCount(); @endphp
                <article class="rounded-3xl border border-line bg-white p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-2.5">
                            <i class="h-3.5 w-3.5 flex-none rounded-full border border-line"
                                style="background-color: {{ $group->color }}"></i>
                            <div class="min-w-0">
                                <strong class="block truncate text-sm">{{ $group->name }}</strong>
                                <span class="text-[11px] text-muted">{{ $group->members->count() }} anggota ·
                                    {{ $used }} project</span>
                            </div>
                        </div>
                        <div class="flex flex-none gap-1.5">
                            <button type="button" @click="edit({{ $group->id }})"
                                class="rounded-full border border-line bg-white px-3 py-1.5 text-[11px] font-extrabold">Edit</button>
                            <form method="POST" action="{{ route('owner.team-groups.destroy', $group) }}"
                                data-confirm="Kelompok &quot;{{ $group->name }}&quot; akan dihapus."
                                data-confirm-title="Hapus kelompok?" data-confirm-button="Ya, hapus"
                                data-confirm-danger="1">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="rounded-full border border-[#f1c7c2] bg-[#fff0ee] px-3 py-1.5 text-[11px] font-extrabold text-[#a83d35]">Hapus</button>
                            </form>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-1.5">
                        @forelse ($group->members as $m)
                            <span class="rounded-full px-2.5 py-1 text-[10px] font-bold text-ink"
                                style="background-color: {{ $group->color }}">{{ $m->name }}</span>
                        @empty
                            <span class="text-[11px] text-muted">Belum ada anggota.</span>
                        @endforelse
                    </div>
                </article>
            @empty
                <div class="rounded-3xl border border-dashed border-line bg-white p-6 text-center text-sm text-muted">
                    Belum ada kelompok. Buat yang pertama lewat form di samping.
                </div>
            @endforelse
        </div>
    </div>
@endsection
