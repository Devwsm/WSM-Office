{{--
    components/org-node.blade.php
    ---------------------------------------------------------------------
    Satu node org-chart + rekursif ke bawahannya. Dipakai di
    owner/organization/index.blade.php.
    Props: $user (model User), $byManager (Collection groupBy manager_id)
    ---------------------------------------------------------------------
--}}
@props(['user', 'byManager'])

@php $children = $byManager->get($user->id, collect()); @endphp

<li class="relative">
    <div class="card-wsm-white inline-flex min-w-52 flex-col gap-0.5 p-3.5!">
        <div class="flex items-center gap-2">
            <strong class="text-sm">{{ $user->name }}</strong>
            <span
                class="badge-wsm-{{ match ($user->role) {'owner' => 'blue','manajer' => 'green','hrd' => 'yellow',default => 'gray'} }}">
                {{ $user->roleLabel() }}
            </span>
        </div>
        <span class="text-xs text-muted">
            {{ $user->job_title ?? '—' }}{{ $user->division ? ' · ' . $user->division : '' }}
        </span>
    </div>

    @if ($children->isNotEmpty())
        <ul class="ml-6 mt-3 flex flex-col gap-3 border-l border-line pl-6">
            @foreach ($children as $child)
                <x-org-node :user="$child" :by-manager="$byManager" />
            @endforeach
        </ul>
    @endif
</li>
