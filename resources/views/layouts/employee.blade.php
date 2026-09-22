{{--
    layouts/employee.blade.php
    ---------------------------------------------------------------------
    Layout Karyawan & Manajer — gaya "mobile app" dengan bottom nav,
    mengikuti visual prototype W.O.S 2.0 (absensi_wsm): hero besar,
    kartu paper/white radius besar, bottom nav pill melayang.
    ---------------------------------------------------------------------
--}}
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- 2026-09-10 — dibutuhkan buat fetch() auto mark-read Inbox modal
        di bawah (pola sama kayak layouts/app.blade.php buat drag-drop
        Work Tracker board, lihat catatan di situ). --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'WSM' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-cream text-ink antialiased">
    @include('partials.flash-data')

    {{-- Popup informasi preview (README Bab 4.2 no. 8): Sambutan "Welcome to
        W.O.S" + Peringatan preview, tampil sekali per login. DIBUNGKUS @unless
        must_change_password — user yang masih wajib ganti password otomatis
        dialihkan ke halaman ini (EnsurePasswordChanged), jadi popup ditunda
        biar gak numpuk sama pengalihan paksa itu; muncul normal begitu
        passwordnya sudah diganti. --}}
    @unless (auth()->user()?->must_change_password)
        @include('partials.entry-popups', ['door' => 'app'])
    @endunless

    <div class="employee-app-shell min-h-screen pb-32 sm:pb-28">
        <div class="employee-app-container mx-auto w-full max-w-3xl px-3 pb-10 pt-3 sm:px-5 sm:pt-5 lg:px-6">
            {{-- 2026-09-10 — `inboxOpened` (beda dari `inboxOpen`, yang itu
                buka/tutup modal): flag one-way, begitu Inbox dibuka
                sekali di kunjungan ini, badge unread di ikon ✉ langsung
                disembunyikan (Alpine, instan, gak nunggu reload) SEKALIGUS
                fetch() ke server nandain semua memo yang kelihatan sebagai
                sudah dibaca (Memo::markAllReadFor(), lihat
                MemoInteractionController::markAllRead()) — biar kunjungan
                App Mode BERIKUTNYA juga gak nampilin badge itu lagi. Mulai
                `true` kalau emang udah 0 unread dari awal, biar gak fetch
                sia-sia. --}}
            <div x-data="{ inboxOpen: false, inboxOpened: {{ ($inboxUnreadCount ?? 0) === 0 ? 'true' : 'false' }} }">
                <header
                    class="employee-app-header mb-6 rounded-wsm-lg border border-line bg-paper/95 p-3 shadow-[0_8px_30px_rgba(16,16,16,0.04)] backdrop-blur sm:mb-8 sm:border-0 sm:bg-transparent sm:p-0 sm:shadow-none">
                    <div class="employee-header-main flex items-center justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-2.5">
                            <div
                                class="grid h-10 w-10 flex-none place-items-center rounded-2xl bg-ink text-[10px] font-black text-white sm:h-11 sm:w-11">
                                WSM
                            </div>
                            <span class="truncate text-xs font-extrabold text-muted">{{ $title ?? 'WSM' }}</span>
                        </div>

                        <div class="employee-header-actions flex flex-none items-center gap-1.5 sm:gap-2">
                            @if (auth()->user()->canViewModule('people'))
                                <a href="{{ route('attendance.recap.index') }}"
                                    class="employee-header-link grid min-h-10 place-items-center rounded-2xl bg-ink px-3 text-[10px] font-extrabold text-white sm:px-3.5">
                                    <span class="employee-header-link-label">Kelola Tim</span>
                                    <span class="employee-header-link-short" aria-hidden="true">Tim</span>
                                </a>
                            @endif
                            @if (auth()->user()->hasAnyDashboardAccess())
                                <a href="{{ route('dashboard.index') }}"
                                    class="employee-header-link grid min-h-10 place-items-center rounded-2xl border border-line bg-white px-3 text-[10px] font-extrabold text-ink sm:px-3.5">
                                    <span class="employee-header-link-label">Dashboard</span>
                                    <span class="employee-header-link-short" aria-hidden="true">Menu</span>
                                </a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}"
                                data-confirm="Kamu akan keluar dari akun ini." data-confirm-title="Keluar akun?"
                                data-confirm-button="Ya, keluar">
                                @csrf
                                <button type="submit" aria-label="Keluar"
                                    class="grid h-10 w-10 place-items-center rounded-2xl bg-[#ece7dd] text-xs font-black">
                                    ⏻
                                </button>
                            </form>
                            <button type="button" aria-label="Inbox"
                                @click="inboxOpen = true; if (!inboxOpened) {
                                    inboxOpened = true;
                                    fetch('{{ route('employee.memo.markAllRead') }}', {
                                        method: 'POST',
                                        headers: {
                                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                            'Accept': 'application/json',
                                        },
                                    });
                                }"
                                class="relative grid h-10 w-10 flex-none place-items-center rounded-2xl bg-[#ece7dd] text-sm">
                                ✉
                                @if ($inboxUnreadCount ?? 0)
                                    <span x-show="!inboxOpened" x-cloak
                                        class="absolute -right-1 -top-1.5 grid min-h-4.5 min-w-4.5 place-items-center rounded-full border-2 border-cream bg-[#ef5c50] px-1 text-[8px] font-black text-white">
                                        {{ $inboxUnreadCount > 9 ? '9+' : $inboxUnreadCount }}
                                    </span>
                                @endif
                            </button>
                            <a href="{{ route('employee.profile.index') }}" aria-label="Profil saya"
                                class="grid h-10 w-10 flex-none place-items-center rounded-2xl bg-ink text-xs font-black text-white">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </a>
                        </div>
                    </div>

                    <div
                        class="employee-header-mobile-meta mt-3 flex items-center justify-between gap-2 border-t border-line pt-2.5 sm:hidden">
                        <span class="truncate text-[10px] font-semibold text-muted">{{ auth()->user()->name }}</span>
                        <span
                            class="flex-none rounded-full bg-white px-2.5 py-1 text-[9px] font-black text-[#68635c]">{{ ucfirst(auth()->user()->role) }}</span>
                    </div>
                </header>

                {{-- Modal Inbox — padanan `openEmployeeInboxV19`. Read jadi
                otomatis begitu modal dibuka (2026-09-10, lihat fetch di
                tombol ✉ + Memo::markAllReadFor()); "Hide" tetap manual,
                form POST biasa ke `memo.toggleHidden` (bukan reply — inbox
                modal prototype gak ada reply, cuma kartu "Info dari Owner"
                di Home yang ada reply). --}}
                <div x-show="inboxOpen" x-cloak
                    class="fixed inset-0 z-50 grid place-items-end bg-black/40 p-0 sm:place-items-center sm:p-4">
                    <div @click.outside="inboxOpen = false"
                        class="max-h-[85vh] w-full max-w-md overflow-y-auto rounded-t-4xl bg-cream p-5 sm:rounded-4xl">
                        <div class="mb-3 flex items-start justify-between">
                            <div>
                                <div class="text-[10px] font-extrabold uppercase text-muted">Employee Inbox</div>
                                <h3 class="text-lg font-black">Inbox</h3>
                                <p class="text-xs text-muted">Memo, reminder, dan MoM yang dikirim ke kamu.</p>
                            </div>
                            <button type="button" @click="inboxOpen = false"
                                class="grid h-9 w-9 flex-none place-items-center rounded-2xl bg-[#ece7dd]">✕</button>
                        </div>

                        <div class="grid gap-2.5">
                            @forelse ($inboxMemos ?? [] as $memo)
                                @php
                                    $isRead = $memo->isReadBy(auth()->user());
                                    $isMom = $memo->type === 'mom';
                                @endphp
                                <article
                                    class="rounded-2xl border bg-white p-3.5 {{ $isRead ? 'border-line' : 'border-ink shadow-[inset_4px_0_0_#111]' }} {{ $isMom && $isRead ? 'shadow-[inset_4px_0_0_#9cc4e8]' : '' }}">
                                    <div class="flex items-start justify-between gap-2">
                                        <div>
                                            <div class="text-[9px] font-extrabold uppercase text-muted">
                                                {{ $isMom ? 'MoM / Meeting' : 'Memo' }}
                                            </div>
                                            <h4 class="text-sm font-black">{{ $memo->title }}</h4>
                                        </div>
                                        <span
                                            class="flex-none rounded-full px-2 py-0.5 text-[8px] font-black uppercase {{ $isRead ? 'bg-[#ece7dd] text-ink' : 'bg-ink text-white' }}">
                                            {{ $isRead ? 'Read' : 'Unread' }}
                                        </span>
                                    </div>
                                    <p class="mt-1.5 text-xs text-ink">{{ $memo->content }}</p>
                                    @if ($isMom && $memo->meeting_date)
                                        <p class="mt-1 text-[10px] text-muted">
                                            Meeting {{ $memo->meeting_date->translatedFormat('d M Y') }}
                                            @if ($memo->attendees)
                                                · {{ $memo->attendees }}
                                            @endif
                                        </p>
                                    @endif
                                    <p class="mt-1.5 text-[10px] text-muted">
                                        From {{ $memo->creator?->name ?? 'Management' }} ·
                                        {{ $memo->created_at->translatedFormat('d M, H:i') }}
                                    </p>
                                    {{-- 2026-09-10 — tombol "Mark Read/Unread" manual DICABUT
                                        (permintaan Arga): begitu Inbox modal ini dibuka, SEMUA
                                        memo yang kelihatan otomatis ke-mark read (fetch di tombol
                                        ✉ header, lihat Memo::markAllReadFor()). Status "Read"/
                                        "Unread" di badge atas tetap ditampilin apa adanya (jujur
                                        soal status kunjungan SEBELUM ini), cuma tombolnya yang
                                        hilang. "Hide" tetap ada, itu aksi beda. --}}
                                    <div class="mt-2.5 flex flex-wrap gap-1.5">
                                        <form method="POST" action="{{ route('employee.memo.toggleHidden', $memo) }}">
                                            @csrf
                                            <button type="submit"
                                                class="rounded-xl border border-line bg-white px-2.5 py-1.5 text-[10px] font-extrabold">
                                                Hide
                                            </button>
                                        </form>
                                    </div>
                                </article>
                            @empty
                                <p class="text-xs text-muted">Inbox kosong.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            @yield('content')
        </div>

        <nav class="bottom-nav-wsm">
            <a href="{{ route('employee.home') }}"
                class="bottom-nav-wsm-item {{ ($navActive ?? '') === 'home' ? 'active' : '' }}">
                <span>⌂</span><em class="not-italic">Home</em>
            </a>
            <a href="{{ route('employee.attendance.history') }}"
                class="bottom-nav-wsm-item {{ ($navActive ?? '') === 'riwayat' ? 'active' : '' }}">
                <span>◷</span><em class="not-italic">Riwayat</em>
            </a>
            <a href="{{ route('employee.home') }}#attendance-card" class="bottom-nav-wsm-item center">
                <span class="text-lg leading-none">+</span>
            </a>
            <a href="{{ route('employee.leave.index') }}"
                class="bottom-nav-wsm-item {{ ($navActive ?? '') === 'pengajuan' ? 'active' : '' }}">
                <span>↗</span><em class="not-italic">Request</em>
            </a>
            <a href="{{ route('employee.profile.index') }}"
                class="bottom-nav-wsm-item {{ ($navActive ?? '') === 'profile' ? 'active' : '' }}">
                <span>◎</span><em class="not-italic">Profile</em>
            </a>
        </nav>
    </div>
</body>

</html>
