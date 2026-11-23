{{--
    dashboard/it/presence.blade.php
    ---------------------------------------------------------------------
    Monitor Login (2026-10-08) — siapa yang online, sedang di halaman apa,
    dan kapan terakhir online. READ-ONLY. Isinya (_presence-list) memuat
    ulang dirinya sendiri lewat AJAX tiap $refreshSeconds detik selama tab
    terlihat. Yang tampil hanya label halaman, bukan URL/isi pekerjaan.
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'IT — Monitor Login', 'navActive' => 'modules'])

@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3.5">
        <div>
            <a href="{{ route('dashboard.index') }}" class="text-[11px] font-extrabold text-muted">← Dashboard</a>
            <h2 class="mt-2 text-[36px] font-black leading-[0.98] tracking-tight">Monitor Login</h2>
            <p class="mt-1 text-[13px] text-muted">Siapa yang sedang online, sedang di halaman apa, dan kapan terakhir
                online.</p>
        </div>
    </div>

    @include('dashboard.it._tabs', ['active' => 'presence'])

    <form method="GET" class="mb-4 flex flex-wrap items-center gap-2">
        @if ($filter)
            <input type="hidden" name="status" value="{{ $filter }}">
        @endif
        @if ($pendingPassword)
            <input type="hidden" name="password" value="pending">
        @endif
        <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama, jabatan, atau divisi..."
            class="min-w-0 flex-1 rounded-2xl border border-line bg-white px-3.5 py-2.5 text-[11px] font-bold text-ink sm:max-w-xs sm:flex-none sm:py-2">
        <button type="submit" class="btn-wsm-white py-2! px-3.5! text-xs">Cari</button>
        @if ($search || $filter || $pendingPassword)
            <a href="{{ route('dashboard.it.presence.index') }}" class="text-[11px] font-extrabold text-muted">Reset</a>
        @endif
        <span class="w-full text-[10px] text-muted sm:ml-auto sm:w-auto">Online = aktif dalam
            {{ (int) (config('presence.online_seconds') / 60) }}
            menit terakhir · Idle = sampai {{ (int) (config('presence.idle_seconds') / 60) }} menit</span>
    </form>

    <div id="presence-live" data-url="{{ request()->fullUrl() }}" data-interval="{{ $refreshSeconds }}">
        @include('dashboard.it._presence-list')
    </div>

    <script>
        (function() {
            var box = document.getElementById('presence-live');
            var busy = false;

            function refresh() {
                if (document.hidden || busy) return;
                busy = true;
                fetch(box.dataset.url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html'
                        },
                        credentials: 'same-origin'
                    })
                    .then(function(r) {
                        if (r.redirected || !r.ok) throw new Error('stop');
                        return r.text();
                    })
                    .then(function(html) {
                        box.innerHTML = html;
                    })
                    .catch(function() {})
                    .finally(function() {
                        busy = false;
                    });
            }

            setInterval(refresh, parseInt(box.dataset.interval, 10) * 1000);
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) refresh();
            });
        })();
    </script>
@endsection
