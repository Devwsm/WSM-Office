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

    Posisi: prototype naruh ini setelah "My Work Tracker" (Fase 9,
    belum ada) dan sebelum "Role Dashboard" entry point. Karena My Work
    Tracker belum dibangun, section ini untuk sementara ditaruh
    langsung sebelum "Latest Attendance" di `home.blade.php` — posisi
    relatif paling dekat ke urutan prototype dari yang SUDAH ada
    sekarang. Pindahkan ke bawah "My Work Tracker" begitu Fase 9
    selesai, biar urutannya kembali persis prototype.
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
