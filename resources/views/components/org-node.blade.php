{{--
    components/org-node.blade.php
    ---------------------------------------------------------------------
    Tampilan HP/tablet (di bawah lg): satu node + bawahannya sebagai daftar
    bertingkat (rekursif). Baris-baris anak (termasuk baris terbelah) digabung
    jadi satu daftar urut — bagan penuh hanya muat di layar desktop.
    Props: $node
    ---------------------------------------------------------------------
--}}
@props(['node'])

@php
    $children = collect($node['rows'])
        ->flatMap(fn($row) => isset($row['split']) ? array_merge(...$row['split']) : $row)
        ->all();
@endphp

<li class="min-w-0">
    <x-org-card :node="$node" mode="list" />

    @if ($node['badge'])
        <span
            class="mt-1 inline-block rounded-full bg-ink px-2 py-0.5 text-[9px] font-black uppercase tracking-wide text-white">{{ $node['badge'] }}</span>
    @endif

    @if ($children !== [] || $node['crew'] !== [])
        <ul class="ml-2 mt-2 grid gap-2 border-l-2 border-line pl-3">
            @foreach ($children as $child)
                <x-org-node :node="$child" />
            @endforeach

            @if ($node['crew'] !== [])
                <li class="min-w-0 rounded-2xl border border-dashed border-[#cfc7b9] bg-white/60 px-3.5 py-2.5">
                    <p class="text-[10px] font-black uppercase tracking-wide text-muted">Crew show</p>
                    <dl class="mt-1 grid gap-0.5 text-xs">
                        @foreach ($node['crew'] as [$role, $names])
                            <div class="flex flex-wrap gap-x-1.5">
                                <dt class="font-extrabold">{{ $role }}</dt>
                                <dd class="min-w-0 wrap-anywhere text-[#5e5951]">{{ $names }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </li>
            @endif
        </ul>
    @endif
</li>
