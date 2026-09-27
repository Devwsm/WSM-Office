{{--
    employee/_team-overview.blade.php
    ---------------------------------------------------------------------
    README #40 — entry point ke Team Overview (Absensi Tim & Progress
    Kerja Tim), khusus role manajer/owner (sama gate dengan route-nya:
    role:manajer,owner). Ditaruh di Home App Mode, bukan header
    (employee-header-actions cuma muat 2 tombol dengan nyaman di layar
    kecil, dan ini punya 2 tujuan link, bukan 1) dan bukan bottom-nav
    (5 slot yang ada sudah tetap, lihat layouts/employee.blade.php).
    ---------------------------------------------------------------------
--}}
@if (auth()->user()->isManajer() || auth()->user()->isOwner())
    <div class="employee-section mt-3.5">
        <p class="mb-2.5 text-xs font-extrabold uppercase tracking-wide text-[#5e5952]">Tim Saya</p>
        <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
            <a href="{{ route('manajer.team.attendance') }}" class="card-wsm-white block">
                <strong class="text-sm">Absensi Tim</strong>
                <p class="mt-1 text-[11px] text-muted">Siapa yang belum absen, lagi cuti, atau lembur hari ini.</p>
            </a>
            <a href="{{ route('manajer.team.work') }}" class="card-wsm-white block">
                <strong class="text-sm">Progress Kerja Tim</strong>
                <p class="mt-1 text-[11px] text-muted">Task overdue dan beban kerja tiap anggota tim.</p>
            </a>
        </div>
    </div>
@endif
