{{--
    partials/page-guide.blade.php
    ---------------------------------------------------------------------
    Tombol "? Panduan" (melayang di kanan bawah) + modal panduan halaman
    dashboard. Di-include SEKALI dari layouts/app.blade.php, jadi tidak
    ada view halaman yang perlu diubah.

    Panduan dicari dari nama route yang sedang dibuka (peta & isi teks
    ada di config/page_guides.php, pencocokannya di App\Support\PageGuide).
    Route tanpa panduan = tombol tidak dirender sama sekali. View juga
    boleh memaksa panduan tertentu lewat
    `@extends('layouts.app', ['pageGuide' => 'kunci'])`.

    Pola modal SAMA dengan modal lain di proyek ini (bottom-sheet di HP,
    di tengah layar di desktop): fixed inset-0 z-50, x-show + x-cloak.
    Tombol z-10 sengaja di bawah overlay sidebar HP (z-20) & sidebar
    (z-30) supaya tidak menimpa keduanya.

    Tutup lewat: tombol ✕, tombol "Mengerti, tutup", tombol Esc, atau
    klik area gelap di luar panel. Isi panel scroll sendiri kalau
    panjang; header & footer tetap terlihat.
    ---------------------------------------------------------------------
--}}
@php
    $pageGuideData = \App\Support\PageGuide::resolve(
        request()->route()?->getName(),
        auth()->user(),
        $pageGuide ?? null,
    );
@endphp

@if ($pageGuideData)
    @php
        $pageGuideTone = match ($pageGuideData['access']['tone'] ?? null) {
            'green' => 'badge-wsm-green',
            'blue' => 'badge-wsm-blue',
            default => 'badge-wsm-gray',
        };
    @endphp
    <div x-data="{
        open: false,
        close() {
            this.open = false;
            this.$nextTick(() => this.$refs.trigger.focus());
        }
    }"
        x-effect="document.body.classList.toggle('overflow-hidden', open); if (open) { $nextTick(() => { $refs.body.scrollTop = 0; $refs.close.focus(); }); }"
        @keydown.escape.window="if (open) close()" class="print:hidden" data-page-guide="{{ $pageGuideData['key'] }}">

        <button type="button" x-ref="trigger" @click="open = true" aria-haspopup="dialog" aria-controls="page-guide-dialog"
            :aria-expanded="open ? 'true' : 'false'"
            class="fixed bottom-4 right-4 z-10 inline-flex items-center gap-2 rounded-full bg-ink py-2.5 pl-2.5 pr-4 text-xs font-extrabold text-white shadow-[0_14px_40px_rgba(16,16,16,0.25)] transition hover:bg-black focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-blue lg:bottom-6 lg:right-6">
            <span class="grid h-6 w-6 place-items-center rounded-full bg-white text-[13px] font-black text-ink"
                aria-hidden="true">?</span>
            Panduan
        </button>

        <div x-show="open" x-cloak x-transition.opacity.duration.150ms style="display:none" @click.self="close()"
            class="fixed inset-0 z-50 grid place-items-end bg-black/40 p-0 sm:place-items-center sm:p-4">
            <div id="page-guide-dialog" role="dialog" aria-modal="true" aria-labelledby="page-guide-title"
                class="flex max-h-[88vh] w-full flex-col overflow-hidden rounded-t-4xl bg-cream sm:max-w-xl sm:rounded-4xl">

                <div class="flex shrink-0 items-start justify-between gap-3 border-b border-line px-5 pb-3.5 pt-5">
                    <div class="min-w-0">
                        <p class="text-[10px] font-black uppercase tracking-wide text-muted">Panduan halaman</p>
                        <h2 id="page-guide-title" class="mt-0.5 text-lg font-black leading-tight">
                            {{ $pageGuideData['title'] }}
                        </h2>
                        @if ($pageGuideData['access'])
                            <span class="{{ $pageGuideTone }} mt-2">{{ $pageGuideData['access']['label'] }}</span>
                        @endif
                    </div>
                    <button type="button" x-ref="close" @click="close()" aria-label="Tutup panduan"
                        class="grid h-9 w-9 shrink-0 place-items-center rounded-2xl bg-[#ece7dd] transition hover:bg-[#e2dccf]">✕</button>
                </div>

                <div x-ref="body" class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-5 pb-5 pt-4">
                    @if ($pageGuideData['summary'] !== '')
                        <p class="text-sm leading-relaxed text-[#5e5951]">{{ $pageGuideData['summary'] }}</p>
                    @endif

                    @foreach ($pageGuideData['sections'] as $heading => $items)
                        <h3 class="mt-5 text-[11px] font-black uppercase tracking-wide text-muted">{{ $heading }}
                        </h3>
                        <ul class="mt-2 grid gap-2">
                            @foreach ($items as $item)
                                <li
                                    class="flex gap-2.5 rounded-2xl border border-line bg-white px-3.5 py-3 text-[13px] leading-relaxed">
                                    <span class="mt-2 h-1.5 w-1.5 flex-none rounded-full bg-ink"
                                        aria-hidden="true"></span>
                                    <span class="min-w-0">
                                        @if (is_array($item))
                                            <strong class="font-extrabold">{{ $item[0] }}</strong>
                                            <span class="text-[#5e5951]">— {{ $item[1] }}</span>
                                        @else
                                            <span class="text-[#5e5951]">{{ $item }}</span>
                                        @endif
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endforeach
                </div>

                <div class="shrink-0 border-t border-line px-5 py-3.5">
                    <button type="button" @click="close()" class="btn-wsm-black w-full py-2.5! text-xs">
                        Mengerti, tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
