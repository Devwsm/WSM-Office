{{--
    dashboard/work/index.blade.php
    ---------------------------------------------------------------------
    Fase 6b — listing penuh Memo & MoM. Tombol tambah/edit/hapus cuma
    kelihatan kalau canManageModule('work') (dicek di controller/route
    lewat middleware module:work,manage, bukan di view — di sini cuma
    nyembunyiin tombolnya aja).
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Work Control — MoM & Memo', 'navActive' => 'modules'])

@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3.5">
        <div>
            <a href="{{ route('dashboard.index') }}" class="text-[11px] font-extrabold text-muted">← Dashboard</a>
            <h2 class="mt-2 text-[36px] font-black leading-[0.98] tracking-tight">MoM &amp; Memo</h2>
            <p class="mt-1 text-[13px] text-muted">Catatan rapat &amp; pengumuman internal.</p>
        </div>
        @if (auth()->user()->canManageModule('work'))
            <a href="{{ route('dashboard.work.create') }}" class="btn-wsm-black">+ Tambah</a>
        @endif
    </div>

    {{-- Tab "Work Control" (audit ronde 6, 2026-09-09 + Timeline Calendar
         2026-09-15) — sekarang 4 sub-halaman: MoM & Memo (ini), Work
         Tracker (board kanban Project/Task), Timeline Calendar (month
         grid deadline tim), Rapat & Action Item (MoM terstruktur). --}}
    <div class="mb-5 flex flex-wrap gap-2">
        <span class="rounded-2xl px-3.5 py-2 text-[11px] font-extrabold text-white"
            style="background-color: var(--work-accent)">MoM &amp; Memo</span>
        <a href="{{ route('dashboard.work.tracker.index') }}"
            class="rounded-2xl border border-line bg-white px-3.5 py-2 text-[11px] font-extrabold text-ink">Work
            Tracker</a>
        <a href="{{ route('dashboard.work.calendar') }}"
            class="rounded-2xl border border-line bg-white px-3.5 py-2 text-[11px] font-extrabold text-ink">Timeline
            Calendar</a>
        <a href="{{ route('dashboard.work.meetings.index') }}"
            class="rounded-2xl border border-line bg-white px-3.5 py-2 text-[11px] font-extrabold text-ink">Rapat
            &amp; Action Item</a>
    </div>

    {{-- README #28 (Bab 4.2 no. 9) — padanan form "Send Reminder" di
         secretary-console prototype v32: 1 submit -> WorkItem baru
         (is_reminder, section "REMINDER / ADMIN") + Memo tertarget ke
         1 karyawan sekaligus. Lihat WorkReminderController. --}}
    @if (auth()->user()->canManageModule('work'))
        <div class="card-wsm-white mb-5" x-data="{ open: false }">
            <button type="button" class="flex w-full items-center justify-between text-left" @click="open = !open">
                <div>
                    <h3 class="text-[15px] font-black">Kirim Reminder</h3>
                    <p class="text-[11px] text-muted">Assign 1 task ke 1 karyawan — otomatis masuk Work Tracker &amp;
                        Info dari Owner orang itu.</p>
                </div>
                <span class="text-[11px] font-extrabold text-muted" x-text="open ? '− Tutup' : '+ Buka'"></span>
            </button>

            <form method="POST" action="{{ route('dashboard.work.reminder.store') }}" class="mt-4 grid gap-4"
                x-show="open" x-cloak>
                @csrf
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="field-label-wsm mb-1.5">Karyawan</label>
                        <select name="pic_employee_id" class="input-wsm" required>
                            <option value="">— Pilih —</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}" @selected(old('pic_employee_id') == $employee->id)>
                                    {{ $employee->name }}@if ($employee->division)
                                        · {{ $employee->division }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('pic_employee_id')
                            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="field-label-wsm mb-1.5">Due Date</label>
                        <input type="date" name="due_date" value="{{ old('due_date') }}" class="input-wsm">
                        @error('due_date')
                            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="field-label-wsm mb-1.5">Pekerjaan / Reminder</label>
                    <input type="text" name="title" value="{{ old('title') }}" class="input-wsm"
                        placeholder="Contoh: Update tracker sebelum meeting" required>
                    @error('title')
                        <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="field-label-wsm mb-1.5">Priority</label>
                        <select name="priority" class="input-wsm" required>
                            @foreach (\App\Models\WorkItem::PRIORITIES as $priority)
                                <option value="{{ $priority }}" @selected(old('priority', 'Medium') === $priority)>
                                    {{ $priority }}
                                </option>
                            @endforeach
                        </select>
                        @error('priority')
                            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="field-label-wsm mb-1.5">Catatan (opsional)</label>
                        <input type="text" name="notes" value="{{ old('notes') }}" class="input-wsm"
                            placeholder="Detail / output yang diharapkan">
                        @error('notes')
                            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <button type="submit" class="btn-wsm-black">Send Reminder</button>
                </div>
            </form>
        </div>
    @endif

    @if ($memos->isEmpty())
        <div class="card-wsm-white text-center">
            <p class="text-xs text-muted">Belum ada memo atau MoM.</p>
        </div>
    @else
        <div class="grid gap-3">
            @foreach ($memos as $memo)
                <div class="rounded-wsm border border-line bg-white p-4.5 {{ $memo->active ? '' : 'opacity-60' }}">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($memo->pinned)
                                    <span class="text-[10px] font-extrabold text-[#a8873d]">📌 PINNED</span>
                                @endif
                                <span
                                    class="rounded-full bg-[#f2f0eb] px-2.5 py-1 text-[10px] font-extrabold text-[#5e5951]">
                                    {{ $memo->typeLabel() }}
                                </span>
                                @unless ($memo->active)
                                    <span
                                        class="rounded-full bg-[#f2ded9] px-2.5 py-1 text-[10px] font-extrabold text-[#a83d35]">Nonaktif</span>
                                @endunless
                            </div>
                            <strong class="mt-1.5 block text-sm">{{ $memo->title }}</strong>
                            <span class="text-[10px] text-muted">
                                {{ $memo->creator->name }} · {{ $memo->created_at->translatedFormat('d M Y') }}
                                @if ($memo->type === 'mom' && $memo->meeting_date)
                                    · Rapat {{ $memo->meeting_date->translatedFormat('d M Y') }}
                                @endif
                                @if ($memo->attendees)
                                    · Peserta: {{ $memo->attendees }}
                                @endif
                            </span>
                            <p class="mt-1 text-[10px] text-muted">Penerima: {{ $memo->audienceLabel() }}</p>
                            <p class="mt-2 whitespace-pre-line text-xs text-ink">{{ $memo->content }}</p>

                            {{-- 2026-09-16 — read/hidden count, datanya udah ada
                                 dari Fase 8 (memo_reads) tapi belum pernah
                                 ditampilin di halaman manajemen. --}}
                            <div class="mt-2.5 flex flex-wrap gap-1.5">
                                <span
                                    class="rounded-full bg-[#e2e9ff] px-2.5 py-1 text-[10px] font-extrabold text-[#213a8f]">
                                    Read {{ $memo->readCount() }}/{{ $memo->audienceCount() }}
                                </span>
                                <span
                                    class="rounded-full bg-[#f2f0eb] px-2.5 py-1 text-[10px] font-extrabold text-[#5e5951]">
                                    Hidden {{ $memo->hiddenCount() }}
                                </span>
                            </div>
                        </div>
                    </div>

                    @if (auth()->user()->canManageModule('work'))
                        <div class="mt-3.5 flex flex-wrap gap-2 border-t border-[#eee8df] pt-3.5">
                            <a href="{{ route('dashboard.work.edit', $memo) }}"
                                class="btn-wsm-white py-2! px-3.5! text-xs">Edit</a>
                            <form method="POST" action="{{ route('dashboard.work.toggleActive', $memo) }}">
                                @csrf
                                <button type="submit" class="btn-wsm-white py-2! px-3.5! text-xs">
                                    {{ $memo->active ? 'Deactivate' : 'Aktifkan' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('dashboard.work.destroy', $memo) }}"
                                data-confirm="{{ $memo->title }} akan dihapus permanen."
                                data-confirm-title="Hapus {{ $memo->typeLabel() }} ini?" data-confirm-button="Ya, hapus"
                                data-confirm-danger="1">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-wsm-red py-2! px-3.5! text-xs">Hapus</button>
                            </form>
                        </div>
                    @endif

                    {{-- Fase 8: thread reply — sisi manajemen liat & bisa
                            balas SEMUA reply karyawan di sini (bukan cuma
                            punya sendiri, thread-nya satu dibagi bareng). --}}
                    @include('memo._thread', [
                        'memo' => $memo,
                        'replyRoute' => route('dashboard.work.reply', $memo),
                    ])
                </div>
            @endforeach
        </div>

        <div class="mt-5">{{ $memos->links() }}</div>
    @endif
@endsection
