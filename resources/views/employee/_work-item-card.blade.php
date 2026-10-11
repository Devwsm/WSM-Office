{{--
    employee/_work-item-card.blade.php
    ---------------------------------------------------------------------
    App Mode (2026-09-09) — padanan `taskCardMarkupV9()` di prototype.
    Dipakai di My Work Tracker (`_work-tracker.blade.php`). Butuh
    `$item` (WorkItem, dengan relasi `project` sudah di-eager-load).

    2026-10-11 — dropdown ubah status + tombol "Update Note" (padanan
    employeeModeView=true di prototype) sekarang ada, lewat
    Employee\MyWorkItemController. Hanya PIC task yang melihat kontrolnya;
    kalau kartu dipakai untuk task orang lain, kirim `editable` = false.
    ---------------------------------------------------------------------
--}}
@php
    $focus = $item->computedFocus();
    $overdue = $item->isOverdue();

    // Padanan progressOptions/badge color di taskCardMarkupV9: Done=green,
    // Follow Up=yellow, overdue=red, On Development=blue, sisanya gray.
    $progressBadgeClass = match (true) {
        $item->progress === 'Done' => 'badge-wsm-green',
        $item->progress === 'Follow Up' => 'badge-wsm-yellow',
        $overdue => 'badge-wsm-red',
        $item->progress === 'On Development' => 'badge-wsm-blue',
        default => 'badge-wsm-gray',
    };

    // Padanan focusClass() di prototype — warna PERSIS (hex sama, nol
    // drift), dikonversi ke inline style karena bukan token Tailwind
    // yang udah ada di app.css.
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

<article
    class="mb-2 rounded-2xl border {{ $overdue ? 'border-[#f1c7c2] bg-[#fff8f7]' : ($item->progress === 'Follow Up' ? 'border-[#f3e3ae] bg-[#fffdf6]' : 'border-line bg-white') }} p-3.5">
    <div class="mb-2 flex items-start justify-between gap-2.5">
        <div class="min-w-0">
            <p class="text-[10px] font-extrabold uppercase tracking-wide text-muted">
                {{ $item->project?->name ?? 'General WSM' }} · {{ $item->section }}
            </p>
            <h3 class="mt-0.5 truncate text-sm font-black">{{ $item->title }}</h3>
        </div>
        <span class="{{ $progressBadgeClass }} flex-none">{{ $item->progress }}</span>
    </div>
    <div class="flex flex-wrap items-center gap-1.5">
        <span class="inline-flex rounded-full px-2 py-1 text-[8px] font-black"
            style="background:{{ $focusColors['bg'] }};color:{{ $focusColors['text'] }}">
            {{ $focus }}
        </span>
        <span class="rounded-full bg-[#f2f0eb] px-2 py-1 text-[10px] font-bold text-[#5e5952]">
            {{ $item->due_date ? $item->due_date->translatedFormat('d M') : 'No date' }}
        </span>
        @if ($item->link)
            <a href="{{ $item->link }}" target="_blank" rel="noopener"
                class="rounded-full bg-[#f2f0eb] px-2 py-1 text-[10px] font-bold text-[#5e5952] underline">
                Open Link
            </a>
        @endif
    </div>
    {{-- Task kerja bareng (2026-09-30): tampilkan rekan PIC lain supaya jelas ini tugas bersama. --}}
    @php
        $coPics = $item->allPics()->reject(fn($u) => $u->id === auth()->id());
    @endphp
    @if ($coPics->isNotEmpty())
        <p class="mt-2 text-[10px] font-bold text-muted">Bersama: {{ $coPics->pluck('name')->implode(', ') }}</p>
    @endif
    @if ($item->notes)
        <p class="mt-2 text-[11px] leading-relaxed text-[#5e5952]">{{ $item->notes }}</p>
    @endif

    @if (($editable ?? true) && $item->hasPic((int) auth()->id()))
        <div class="mt-3 flex items-center gap-2" x-data="{ noteOpen: false }" @keydown.escape.window="noteOpen = false">
            <form method="POST" action="{{ route('employee.workItems.progress', $item) }}" class="min-w-0 flex-1">
                @csrf
                @method('PATCH')
                <label for="progress-{{ $item->id }}" class="sr-only">Status task</label>
                <select id="progress-{{ $item->id }}" name="progress" class="input-wsm py-2! text-xs"
                    onchange="this.form.submit()">
                    @foreach (\App\Models\WorkItem::PROGRESS_OPTIONS as $option)
                        <option value="{{ $option }}" @selected($item->progress === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </form>
            <button type="button" @click="noteOpen = true" class="btn-wsm-white flex-none py-2! px-3.5! text-xs">
                Update Note
            </button>

            <div x-show="noteOpen" x-cloak
                class="fixed inset-0 z-50 grid place-items-end bg-black/40 p-0 sm:place-items-center sm:p-4"
                x-effect="if (noteOpen) $nextTick(() => $refs.note && $refs.note.focus())" role="dialog"
                aria-modal="true" aria-labelledby="note-modal-title-{{ $item->id }}">
                <div @click.outside="noteOpen = false"
                    class="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-t-4xl bg-cream p-5 sm:rounded-4xl">
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="text-[10px] font-extrabold uppercase text-muted">Catatan task</div>
                            <h3 id="note-modal-title-{{ $item->id }}" class="truncate text-lg font-black">
                                {{ $item->title }}</h3>
                        </div>
                        <button type="button" @click="noteOpen = false" aria-label="Tutup"
                            class="grid h-9 w-9 flex-none place-items-center rounded-2xl bg-[#ece7dd]">✕</button>
                    </div>

                    <form method="POST" action="{{ route('employee.workItems.note', $item) }}" class="grid gap-3">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label class="field-label-wsm" for="note-{{ $item->id }}">Catatan</label>
                            <textarea id="note-{{ $item->id }}" name="notes" x-ref="note" rows="5" maxlength="2000"
                                class="input-wsm mt-1.5" placeholder="Tulis perkembangan atau kendala singkat...">{{ $item->notes }}</textarea>
                            <p class="mt-1 text-[10px] text-muted">Maksimal 2000 karakter. Kosongkan untuk menghapus
                                catatan.</p>
                        </div>
                        <div class="mt-1.5 grid grid-cols-2 gap-2">
                            <button type="button" @click="noteOpen = false" class="btn-wsm-white">Batal</button>
                            <button type="submit" class="btn-wsm-black">Simpan Catatan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</article>
