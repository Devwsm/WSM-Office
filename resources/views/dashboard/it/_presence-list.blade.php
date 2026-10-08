{{--
    dashboard/it/_presence-list.blade.php
    ---------------------------------------------------------------------
    Isi Monitor Login yang di-refresh otomatis (kartu ringkasan + daftar
    karyawan). Dirender penuh oleh dashboard/it/presence.blade.php dan
    sendirian (tanpa layout) untuk request AJAX dari halaman itu.
    ---------------------------------------------------------------------
--}}
@use('App\Support\Presence')
@php
    $statusStyle = [
        Presence::ONLINE => ['label' => 'Online', 'pill' => 'bg-[#e8f8e8] text-[#286231]', 'dot' => 'bg-[#2fa84f]'],
        Presence::IDLE => ['label' => 'Idle', 'pill' => 'bg-[#fff0bd] text-[#6b4a00]', 'dot' => 'bg-[#e0a800]'],
        Presence::OFFLINE => ['label' => 'Offline', 'pill' => 'bg-[#eeeae3] text-[#4e4a43]', 'dot' => 'bg-[#a8a298]'],
    ];
    $query = fn(?string $status) => array_filter(['status' => $status, 'q' => $search]);
    $cards = [
        [null, 'Semua', $counts['total']],
        [Presence::ONLINE, 'Online', $counts[Presence::ONLINE]],
        [Presence::IDLE, 'Idle', $counts[Presence::IDLE]],
        [Presence::OFFLINE, 'Offline', $counts[Presence::OFFLINE]],
    ];
@endphp

<div class="mb-4 grid grid-cols-2 gap-2.5 lg:grid-cols-4">
    @foreach ($cards as [$key, $label, $total])
        <a href="{{ route('dashboard.it.presence.index', $query($key)) }}"
            class="rounded-wsm-lg border bg-white p-3.5 {{ $filter === $key ? 'border-[#111] ring-1 ring-[#111]' : 'border-line' }}">
            <p class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-wide text-muted">
                @if ($key)
                    <span class="inline-block h-2 w-2 rounded-full {{ $statusStyle[$key]['dot'] }}"></span>
                @endif
                {{ $label }}
            </p>
            <strong class="mt-1 block text-[28px] font-black leading-none">{{ $total }}</strong>
        </a>
    @endforeach
</div>

@if ($rows->isEmpty())
    <div class="card-wsm-white text-center">
        <p class="text-xs text-muted">Tidak ada karyawan yang cocok dengan filter ini.</p>
    </div>
@else
    <div class="grid gap-2.5">
        @foreach ($rows as $row)
            @php
                $u = $row['user'];
                $style = $statusStyle[$row['status']];
                $loggedOut =
                    $u->last_seen_at &&
                    $u->last_logout_at &&
                    $u->last_logout_at->greaterThanOrEqualTo($u->last_seen_at);
            @endphp
            <div
                class="flex flex-wrap items-center justify-between gap-x-4 gap-y-3 rounded-wsm border border-line bg-white p-4">
                <div class="flex min-w-0 items-center gap-3 sm:w-64">
                    <div class="relative flex-none">
                        <x-avatar :user="$u" class="h-11 w-11 rounded-2xl text-sm" />
                        <span
                            class="absolute -bottom-0.5 -right-0.5 h-3.5 w-3.5 rounded-full border-2 border-white {{ $style['dot'] }}"></span>
                    </div>
                    <div class="min-w-0">
                        <strong class="block truncate text-sm">{{ $u->name }}</strong>
                        <span
                            class="block truncate text-[11px] text-muted">{{ $u->job_title ?? $u->roleLabel() }}</span>
                    </div>
                </div>

                <div class="min-w-0 flex-1 basis-48">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-black {{ $style['pill'] }}">
                        <span
                            class="inline-block h-1.5 w-1.5 rounded-full {{ $style['dot'] }}"></span>{{ $style['label'] }}
                    </span>
                    <p class="mt-1.5 truncate text-xs">
                        @if (!$u->last_seen_at)
                            <span class="text-muted">Belum ada data aktivitas</span>
                        @elseif ($loggedOut)
                            <span class="text-muted">Sudah logout</span>
                            @if ($u->last_seen_label)
                                <span class="text-muted">· terakhir di</span>
                                <strong>{{ $u->last_seen_label }}</strong>
                            @endif
                        @else
                            <span
                                class="text-muted">{{ $row['status'] === Presence::OFFLINE ? 'Terakhir di' : 'Sedang di' }}</span>
                            <strong>{{ $u->last_seen_label ?? 'Halaman lain' }}</strong>
                        @endif
                    </p>
                </div>

                <div class="grid flex-none grid-cols-2 gap-x-6 gap-y-1 text-[11px] sm:w-80">
                    <div>
                        <span class="block text-[10px] font-extrabold uppercase text-muted">Terakhir online</span>
                        @if ($u->last_seen_at)
                            <strong class="block">{{ $u->last_seen_at->diffForHumans() }}</strong>
                            <span class="text-muted">{{ $u->last_seen_at->translatedFormat('d M, H:i') }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </div>
                    <div>
                        <span class="block text-[10px] font-extrabold uppercase text-muted">Login terakhir</span>
                        @if ($u->last_login_at)
                            <strong class="block">{{ $u->last_login_at->translatedFormat('d M, H:i') }}</strong>
                            <span class="text-muted">{{ $u->last_login_at->diffForHumans() }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

<p class="mt-3 text-[10px] text-muted">Diperbarui {{ now()->translatedFormat('H:i:s') }}</p>
