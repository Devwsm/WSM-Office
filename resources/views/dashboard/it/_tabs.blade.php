{{--
    dashboard/it/_tabs.blade.php
    ---------------------------------------------------------------------
    Tab bar menu IT SATU sumber untuk Audit Log, System Changelog, Monitor
    Login, dan Reset Password (sebelumnya disalin manual di 4 halaman, dan
    tidak bisa turun baris sehingga meluber di HP). Pola sama dengan
    dashboard/work/_tabs.blade.php.

    Pakai: @include('dashboard.it._tabs', ['active' => 'audit'])
    Kunci $active: audit | changelog | presence | password
    ---------------------------------------------------------------------
--}}
@php
    $itTabs = [
        'audit' => ['Audit Log', route('dashboard.it.index')],
        'changelog' => ['System Changelog', route('dashboard.it.changelog.index')],
        'presence' => ['Monitor Login', route('dashboard.it.presence.index')],
    ];
    if (auth()->user()->canManageModule('it')) {
        $itTabs['password'] = ['Reset Password', route('dashboard.it.password-resets.index')];
    }
@endphp
<nav class="mb-5 flex flex-wrap gap-2" aria-label="Menu IT">
    @foreach ($itTabs as $key => [$label, $url])
        @if ($key === ($active ?? null))
            <span aria-current="page"
                class="inline-flex min-h-10 items-center rounded-2xl bg-ink px-3.5 py-2 text-[11px] font-extrabold text-white sm:min-h-0">{{ $label }}</span>
        @else
            <a href="{{ $url }}"
                class="inline-flex min-h-10 items-center rounded-2xl border border-line bg-white px-3.5 py-2 text-[11px] font-extrabold text-ink sm:min-h-0">{{ $label }}</a>
        @endif
    @endforeach
</nav>
