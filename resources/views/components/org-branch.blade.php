{{--
    components/org-branch.blade.php
    ---------------------------------------------------------------------
    Tampilan desktop (lg+): satu node + anak-anaknya sebagai pohon horizontal
    (rekursif). Garis digambar oleh CSS `.org-*` di resources/css/app.css.
    Props: $node, $edge ('' | 'is-first' | 'is-last' | 'is-only' dst., class
    posisi di antara saudaranya yang diberikan induknya).
    ---------------------------------------------------------------------
--}}
@props(['node', 'edge' => ''])

@php
    /** Class posisi (is-first/is-last/is-only) untuk daftar saudara. */
    $edges = function (int $count, bool $first = true, bool $last = true): array {
        $out = array_fill(0, $count, '');
        if ($count === 1 && $first && $last) {
            $out[0] = 'is-only';

            return $out;
        }
        if ($first) {
            $out[0] = 'is-first';
        }
        if ($last) {
            $out[$count - 1] = trim($out[$count - 1] . ' is-last');
        }

        return $out;
    };
@endphp

<li class="org-branch {{ $edge }} {{ $node['dotted'] ? 'is-dotted' : '' }}">
    <x-org-card :node="$node" mode="chart" />

    @if ($node['badge'])
        <span
            class="mt-1 rounded-full bg-ink px-2 py-0.5 text-[8px] font-black uppercase tracking-wide text-white">{{ $node['badge'] }}</span>
    @endif

    @foreach ($node['rows'] as $row)
        @if (isset($row['split']))
            @php
                [$left, $right] = $row['split'];
                $leftEdges = $edges(count($left), true, false);
                $rightEdges = $edges(count($right), false, true);
            @endphp
            <div class="org-split">
                <ul class="org-half is-left">
                    @foreach ($left as $i => $child)
                        <x-org-branch :node="$child" :edge="$leftEdges[$i]" />
                    @endforeach
                </ul>
                <div class="org-gap" aria-hidden="true"></div>
                <ul class="org-half is-right">
                    @foreach ($right as $i => $child)
                        <x-org-branch :node="$child" :edge="$rightEdges[$i]" />
                    @endforeach
                </ul>
            </div>
        @else
            @php
                $rowEdges = $edges(count($row));
                $isStack = $node['layout'] === 'stack';
                $rowHasZones = collect($row)->contains(fn($c) => !empty($c['zone']));
            @endphp
            <ul class="{{ $isStack ? 'org-stack' : 'org-tier' }} {{ $rowHasZones ? 'is-zones' : '' }}">
                @foreach ($row as $i => $child)
                    <x-org-branch :node="$child" :edge="$isStack ? '' : $rowEdges[$i]" />
                @endforeach
            </ul>
        @endif
    @endforeach

    @if ($node['crew'] !== [])
        <div class="mt-3 w-36 rounded-xl border border-dashed border-[#cfc7b9] bg-white/60 px-2 py-1.5 text-left">
            <p class="text-center text-[8px] font-black uppercase tracking-wide text-muted">Crew show</p>
            <dl class="mt-1 grid gap-0.5 text-[9px] leading-tight">
                @foreach ($node['crew'] as [$role, $names])
                    <div class="wrap-anywhere">
                        <dt class="inline font-extrabold">{{ $role }}:</dt>
                        <dd class="inline text-[#5e5951]">{{ $names }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    @endif

    @if ($node['zone'])
        <div class="org-zone">
            <div
                class="org-zone-bar {{ $node['zone'] === 'external' ? 'bg-[#fbd460] text-ink' : 'bg-[#e90ea4] text-white' }}">
                {{ $node['zone'] }}
            </div>
        </div>
    @endif
</li>
