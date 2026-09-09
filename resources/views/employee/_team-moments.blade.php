{{--
    employee/_team-moments.blade.php
    ---------------------------------------------------------------------
    App Mode quick win (2026-09-09, ronde 5 dari audit README) —
    padanan `teamCelebrationMarkup()` / `celebrationRows(45)` di
    prototype: daftar ulang tahun & work anniversary SEMUA karyawan
    (bukan cuma diri sendiri, beda dari `_milestones.blade.php`) yang
    jatuh dalam 45 hari ke depan, maksimal 6 baris, diurut dari yang
    paling dekat.

    `$teamMoments` dioper dari HomeController (butuh query lintas-user,
    beda dari Milestones pribadi yang manggil `auth()->user()`
    langsung). Section ini disembunyikan total kalau kosong — sama
    seperti prototype (`if(!rows.length)return''`).

    Posisi: PERSIS urutan prototype sejak Fase 9 (2026-09-09) — setelah
    "My Work Tracker", sebelum "Role Dashboard" entry point (tombol
    "Dashboard" di header, lihat layouts/employee.blade.php). Sebelum
    Fase 9 ada, section ini sempat ditaruh sementara sebelum "Latest
    Attendance" (ronde 5) karena My Work Tracker belum dibangun —
    sekarang sudah di posisi final.
    ---------------------------------------------------------------------
--}}
@if ($teamMoments->isNotEmpty())
    <div class="card-wsm-white mb-3.5">
        <p class="mb-2.5 text-xs font-extrabold uppercase tracking-wide text-[#5e5952]">Team Moments</p>
        <div class="grid gap-2">
            @foreach ($teamMoments as $row)
                <div
                    class="flex items-center justify-between gap-3 rounded-2xl border border-line bg-white px-3.5 py-2.5">
                    <div class="min-w-0">
                        <strong class="block truncate text-xs font-black">
                            {{ $row['type'] === 'birthday' ? '🎂' : '✦' }} {{ $row['user']->name }}
                        </strong>
                        <span class="text-[10px] text-muted">
                            {{ $row['type'] === 'birthday' ? 'Birthday' : 'Work Anniversary' }}
                            @if ($row['years'])
                                · {{ $row['years'] }} tahun
                            @endif
                        </span>
                    </div>
                    <strong class="flex-none text-xs">{{ $row['date']->translatedFormat('d M') }}</strong>
                </div>
            @endforeach
        </div>
    </div>
@endif
