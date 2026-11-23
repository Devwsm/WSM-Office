{{--
    vendor/pagination/wsm.blade.php
    ---------------------------------------------------------------------
    Tampilan pagination SATU-SATUNYA di aplikasi (dipasang sebagai bawaan lewat
    Paginator::defaultView() di AppServiceProvider), menggantikan tampilan
    Tailwind bawaan Laravel yang tidak sesuai tema.

    - HP (< 768px): tombol Sebelumnya / Berikutnya (besar, mudah disentuh) dan
      keterangan "Hal X dari Y". Deretan nomor halaman terlalu lebar untuk HP.
    - Tablet ke atas: nomor halaman bertitik-titik (…), halaman aktif hitam
      seperti tombol aktif di sidebar.
    - Semua tombol minimal 40px (area sentuh) dan memakai palet cream/paper.
    ---------------------------------------------------------------------
--}}
@if ($paginator->hasPages())
    @php
        $btn =
            'grid h-10 min-w-10 place-items-center rounded-2xl border border-line bg-white px-3.5 text-xs font-extrabold text-ink transition hover:bg-cream focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-blue';
        $btnOff =
            'grid h-10 min-w-10 cursor-not-allowed place-items-center rounded-2xl border border-line bg-white px-3.5 text-xs font-extrabold text-ink opacity-40';
    @endphp
    <nav role="navigation" aria-label="Navigasi halaman"
        class="wsm-pagination flex flex-col items-center gap-3 md:flex-row md:flex-wrap md:justify-between">
        <p class="text-[11px] font-bold text-muted">
            @if ($paginator->firstItem())
                Menampilkan {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }}
            @else
                {{ $paginator->count() }} data
            @endif
        </p>

        {{-- HP & tablet kecil: Sebelumnya / Hal X dari Y / Berikutnya --}}
        <div class="flex w-full items-center justify-between gap-2 md:hidden">
            @if ($paginator->onFirstPage())
                <span class="{{ $btnOff }}" aria-disabled="true">← Sebelumnya</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $btn }}">← Sebelumnya</a>
            @endif
            <span
                class="text-[11px] font-extrabold text-muted">{{ 'Hal ' . $paginator->currentPage() . ' dari ' . $paginator->lastPage() }}</span>
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $btn }}">Berikutnya →</a>
            @else
                <span class="{{ $btnOff }}" aria-disabled="true">Berikutnya →</span>
            @endif
        </div>

        {{-- Tablet & desktop: nomor halaman --}}
        <div class="hidden flex-wrap items-center justify-center gap-1.5 md:flex">
            @if ($paginator->onFirstPage())
                <span class="{{ $btnOff }}" aria-disabled="true" aria-label="Sebelumnya">←</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $btn }}"
                    aria-label="Sebelumnya">←</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span
                        class="grid h-10 min-w-8 place-items-center text-xs font-extrabold text-muted">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page"
                                class="grid h-10 min-w-10 place-items-center rounded-2xl border border-ink bg-ink px-3 text-xs font-extrabold text-white">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="{{ $btn }}"
                                aria-label="Ke halaman {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $btn }}"
                    aria-label="Berikutnya">→</a>
            @else
                <span class="{{ $btnOff }}" aria-disabled="true" aria-label="Berikutnya">→</span>
            @endif
        </div>
    </nav>
@endif
