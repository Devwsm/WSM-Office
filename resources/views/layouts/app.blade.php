{{--
    layouts/app.blade.php
    ---------------------------------------------------------------------
    Layout untuk sisi Owner & Manajer (dashboard dengan sidebar).
    Gaya visual disamakan dengan prototype W.O.S 2.0 (absensi_wsm):
    palet cream/paper, brand mark hitam, nav pill aktif hitam.
    Sidebar SAAT INI statis — mulai Fase 12 (Dashboard Access) harus
    menyesuaikan otomatis sesuai akses user.
    ---------------------------------------------------------------------
--}}
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Dashboard' }} — WSM Office System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-cream text-ink antialiased">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen lg:grid lg:grid-cols-[260px_1fr]">
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-30 flex h-screen w-64 -translate-x-full flex-col border-r border-line bg-paper p-4 transition-transform duration-200 ease-in-out lg:sticky lg:top-0 lg:translate-x-0">
            <div class="flex items-center gap-3 px-1 pb-6 pt-1">
                <div class="grid h-11 w-11 place-items-center rounded-2xl bg-ink text-[11px] font-black text-white">
                    WSM
                </div>
                <div class="leading-tight">
                    <strong class="block text-sm">WSM Office</strong>
                    <span class="text-[11px] text-muted">Whisnu Santika Music</span>
                </div>
            </div>

            <nav class="grid gap-1.5 text-sm">
                <a href="{{ route('owner.dashboard') }}"
                    class="rounded-2xl px-3.5 py-3 font-extrabold {{ ($navActive ?? '') === 'dashboard' ? 'bg-ink text-white' : 'text-[#5e5951] hover:bg-white' }}">
                    Dashboard
                </a>
                {{-- TODO Fase 1: menu Employees & Organization --}}
                {{-- TODO Fase 2: menu Attendance --}}
                {{-- TODO Fase 5: menu Requests / Team Approval --}}
                {{-- TODO Fase 6: menu MoM & Memos --}}
                {{-- TODO Fase 7: menu Projects & Timeline --}}
                {{-- TODO Fase 8: menu KPI & Performance --}}
                {{-- TODO Fase 9: menu Contracts --}}
                {{-- TODO Fase 10: menu Payroll --}}
                {{-- TODO Fase 11: menu Project Budgeting & Royalty --}}
                {{-- TODO Fase 12: menu Settings & Dashboard Access --}}
            </nav>

            <div class="mt-auto border-t border-line pt-3.5">
                <div class="flex items-center gap-2.5 px-1 pb-3">
                    <div class="grid h-9 w-9 place-items-center rounded-xl bg-[#ece7dd] text-xs font-black">
                        {{ strtoupper(substr(auth()->user()->name ?? '?', 0, 1)) }}
                    </div>
                    <div class="min-w-0 leading-tight">
                        <strong class="block truncate text-xs">{{ auth()->user()->name }}</strong>
                        <span class="text-[10px] capitalize text-muted">{{ auth()->user()->role }}</span>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn-wsm-white w-full py-2.5! text-xs">Keluar</button>
                </form>
            </div>
        </aside>

        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 z-20 bg-black/40 lg:hidden">
        </div>

        <div class="flex min-w-0 flex-col">
            <header class="flex items-center justify-between gap-4 px-4 py-4 lg:hidden">
                <button @click="sidebarOpen = !sidebarOpen"
                    class="grid h-10 w-10 place-items-center rounded-2xl bg-[#ece7dd]">
                    ☰
                </button>
                <h1 class="text-sm font-extrabold">{{ $title ?? 'Dashboard' }}</h1>
                <div class="w-10"></div>
            </header>

            <main class="flex-1 px-4 pb-16 pt-2 lg:px-8 lg:pb-20 lg:pt-8">
                @yield('content')
            </main>
        </div>
    </div>
</body>

</html>
