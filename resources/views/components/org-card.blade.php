{{--
    components/org-card.blade.php
    ---------------------------------------------------------------------
    Satu kartu jabatan di Struktur Organisasi. Dipakai dua tampilan:
      mode="chart" -> kartu kecil bertengah di bagan desktop (lg+)
      mode="list"  -> kartu selebar baris di daftar bertingkat (HP/tablet)
    Kartu yang berisi orang berupa <button> (membuka pop-up akses lewat
    open('<id>') milik x-data di halaman); posisi kosong berupa <div>.

    Props: $node (node hasil OrganizationController::normalize), $mode
    ---------------------------------------------------------------------
--}}
@props(['node', 'mode' => 'chart'])

@php
    $tones = [
        'pink' => 'border-[#d9a3a3] bg-[#f5cccc]',
        'blue' => 'border-[#7f9fe0] bg-[#aac4f5]',
        'green' => 'border-[#8fc383] bg-[#bde3b4]',
        'yellow' => 'border-[#e0c77a] bg-[#fbeab4]',
        'white' => 'border-[#cfc7b9] bg-white',
    ];

    $isChart = $mode === 'chart';
    $tag = $node['clickable'] ? 'button' : 'div';

    $layout = $isChart
        ? 'flex min-h-14 flex-col items-center justify-center gap-0.5 rounded-xl px-1.5 py-2 text-center ' .
            ($node['depth'] <= 1 ? 'w-36' : 'w-[var(--org-card-w)]')
        : 'flex w-full items-center justify-between gap-3 rounded-2xl px-3.5 py-2.5 text-left';

    $classes = implode(' ', [
        'relative min-w-0 overflow-hidden border',
        $layout,
        $tones[$node['tone']] ?? $tones['white'],
        $node['dotted'] || $node['vacant'] ? 'border-dashed' : '',
        $node['clickable']
            ? 'cursor-pointer transition hover:-translate-y-0.5 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-blue'
            : '',
    ]);

    $titleClass = $isChart ? 'text-[9px]' : 'text-[10px]';
    $nameClass = $isChart ? 'text-[11px]' : 'text-[13px]';
@endphp

<{{ $tag }}
    @if ($node['clickable']) type="button" @click="open('{{ $node['id'] }}')" aria-haspopup="dialog"
        aria-label="Lihat akses {{ collect($node['people'])->pluck('name')->implode(' / ') }}, {{ $node['title'] }}" @endif
    class="{{ $classes }}">
    <span class="min-w-0">
        @if ($node['title'])
            <span
                class="block {{ $titleClass }} font-semibold leading-tight text-ink/60 wrap-anywhere">{{ $node['title'] }}</span>
        @endif
        <span class="block {{ $nameClass }} font-extrabold leading-tight wrap-anywhere">
            @if ($node['vacant'])
                <span class="font-bold text-muted">Vacant</span>
            @else
                @foreach ($node['people'] as $person)
                    @if (!$loop->first)
                        <span class="font-semibold text-ink/50">/</span>
                    @endif
                    {{ $person['name'] }}
                    @if ($person['freelance'])
                        <span class="block text-[8px] font-semibold text-ink/55">(freelance)</span>
                    @endif
                @endforeach
            @endif
        </span>
    </span>

    @if (!$isChart && $node['zone'])
        <span
            class="flex-none rounded-full {{ $node['zone'] === 'external' ? 'bg-[#fbd460] text-ink' : 'bg-[#e90ea4] text-white' }} px-2 py-0.5 text-[9px] font-black uppercase tracking-wide">{{ $node['zone'] }}</span>
    @elseif (!$isChart && $node['clickable'])
        <span class="flex-none text-base font-black text-ink/40" aria-hidden="true">›</span>
    @endif

    @if ($node['accent'])
        <span class="absolute inset-x-0 bottom-0 h-1 bg-[#e6ec3f]" aria-hidden="true"></span>
    @endif
    </{{ $tag }}>
