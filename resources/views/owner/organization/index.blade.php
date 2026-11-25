{{--
    owner/organization/index.blade.php
    ---------------------------------------------------------------------
    Struktur Organisasi dari akun aktif + Atasan Langsung (gaya gambar
    "ORGANIZATION NEW", 2026-11-25). Akses tiap orang
    muncul di pop-up saat namanya diklik (dihitung server, lihat
    OrganizationController).

    Dua tampilan dari data yang sama:
      - HP & tablet (< lg): daftar bertingkat, tanpa geser samping.
      - Desktop (lg+): bagan pohon horizontal seperti gambar; diperkecil
        otomatis supaya muat (orgChart.fit), dan kalau tetap terlalu lebar
        bergeser di dalam kotaknya sendiri.
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Struktur Organisasi', 'navActive' => 'organization'])

@section('content')
    <div x-data="orgChart(@js($payload))" x-init="$nextTick(() => fit())" @resize.window.debounce.150ms="fit()"
        @keydown.escape.window="if (openId) close()" class="min-w-0">

        <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-[34px] font-black leading-[0.95] tracking-tight sm:text-[40px]">Struktur Organisasi</h2>
                <p class="mt-1 text-[15px] text-muted">Disusun dari data Atasan Langsung akun yang ada ({{ $totalAkun }}
                    akun aktif). Klik nama untuk melihat
                    akses sistemnya.</p>
            </div>
            <a href="{{ route('owner.employees.index') }}" class="btn-wsm-white">Kelola Karyawan</a>
        </div>

        {{-- Legenda warna (berdasarkan tim kerja akun) --}}
        <ul class="mb-4 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-[11px] font-bold text-[#5e5951]">
            <li class="flex items-center gap-1.5"><span
                    class="h-3 w-3 rounded border border-[#d9a3a3] bg-[#f5cccc]"></span>Management / HR</li>
            <li class="flex items-center gap-1.5"><span
                    class="h-3 w-3 rounded border border-[#7f9fe0] bg-[#aac4f5]"></span>Marketing</li>
            <li class="flex items-center gap-1.5"><span
                    class="h-3 w-3 rounded border border-[#8fc383] bg-[#bde3b4]"></span>Creative</li>
            <li class="flex items-center gap-1.5"><span
                    class="h-3 w-3 rounded border border-[#e0c77a] bg-[#fbeab4]"></span>GA / Operations</li>
            <li class="flex items-center gap-1.5"><span
                    class="h-3 w-3 rounded border border-[#cfc7b9] bg-white"></span>Lainnya</li>
        </ul>

        @if ($tree === [])
            <div class="card-wsm">
                <p class="text-sm text-muted">Belum ada karyawan. Tambah dari halaman Karyawan dulu.</p>
            </div>
        @else
            {{-- HP & tablet: daftar bertingkat --}}
            <div class="card-wsm lg:hidden">
                <ul class="grid gap-2">
                    @foreach ($tree as $root)
                        <x-org-node :node="$root" />
                    @endforeach
                </ul>
            </div>

            {{-- Desktop: bagan pohon --}}
            <div x-ref="wrap" class="card-wsm hidden overflow-x-auto lg:block">
                <div x-ref="chart" class="org-chart">
                    <ul class="m-0 flex list-none justify-center p-0">
                        @foreach ($tree as $root)
                            <x-org-branch :node="$root" />
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        {{-- Pop-up akses (bottom-sheet di HP, di tengah layar di desktop — pola sama dengan Panduan) --}}
        <div x-show="openId" x-cloak x-transition.opacity.duration.150ms style="display:none" @click.self="close()"
            class="fixed inset-0 z-50 grid place-items-end bg-black/40 p-0 sm:place-items-center sm:p-4">
            <div role="dialog" aria-modal="true" aria-labelledby="org-dialog-title"
                class="flex max-h-[88dvh] w-full flex-col overflow-hidden rounded-t-4xl bg-cream sm:max-w-xl sm:rounded-4xl">

                <div class="flex shrink-0 items-start justify-between gap-3 border-b border-line px-5 pb-3.5 pt-5">
                    <div class="min-w-0">
                        <p class="text-[10px] font-black uppercase tracking-wide text-muted">Akses sistem</p>
                        <h2 id="org-dialog-title" class="mt-0.5 text-lg font-black leading-tight wrap-anywhere"
                            x-text="node ? (node.title || people.map(p => p.name).join(' / ')) : ''"></h2>
                        <template x-if="node && node.zone">
                            <span
                                class="mt-2 inline-block rounded-full px-2 py-0.5 text-[9px] font-black uppercase tracking-wide"
                                :class="node.zone === 'external' ? 'bg-[#fbd460] text-ink' : 'bg-[#e90ea4] text-white'"
                                x-text="node.zone"></span>
                        </template>
                    </div>
                    <button type="button" x-ref="close" @click="close()" aria-label="Tutup"
                        class="grid h-9 w-9 shrink-0 place-items-center rounded-2xl bg-[#ece7dd] transition hover:bg-[#e2dccf]">✕</button>
                </div>

                <div x-ref="body" class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-5 pb-5 pt-4">
                    <div class="grid gap-4">
                        <template x-for="(person, index) in people" :key="index">
                            <section class="min-w-0 rounded-wsm border border-line bg-white p-4">
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <strong class="text-base wrap-anywhere" x-text="person.name"></strong>
                                    <span x-show="person.freelance"
                                        class="text-[11px] font-bold text-muted">(freelance)</span>
                                    <template x-if="person.info">
                                        <span class="badge-wsm-blue" x-text="person.info.role"></span>
                                    </template>
                                </div>

                                {{-- Belum punya akun --}}
                                <template x-if="!person.info">
                                    <p class="mt-2 text-[13px] leading-relaxed text-[#5e5951]">
                                        Belum punya akun di WSM Office, jadi belum ada akses sistem.
                                    </p>
                                </template>

                                <template x-if="person.info">
                                    <div class="mt-3">
                                        <p x-show="person.info.fullAccess"
                                            class="rounded-2xl bg-[#e9f7ec] px-3.5 py-2.5 text-[13px] font-bold text-[#1d6b34]">
                                            Akses penuh (Manage) ke seluruh modul dashboard.
                                        </p>

                                        <div x-show="!person.info.fullAccess">
                                            <h3 class="text-[11px] font-black uppercase tracking-wide text-muted">Akses
                                                dashboard</h3>
                                            <p x-show="granted(person.info).length === 0"
                                                class="mt-2 text-[13px] text-[#5e5951]">
                                                Belum punya akses modul dashboard.
                                            </p>
                                            <ul class="mt-2 grid gap-1.5">
                                                <template x-for="module in granted(person.info)" :key="module.key">
                                                    <li
                                                        class="flex items-center justify-between gap-3 rounded-2xl border border-line bg-cream/60 px-3.5 py-2">
                                                        <span class="min-w-0">
                                                            <span class="block text-[13px] font-extrabold wrap-anywhere"
                                                                x-text="module.label"></span>
                                                            <span class="block text-[11px] text-muted wrap-anywhere"
                                                                x-text="module.desc"></span>
                                                        </span>
                                                        <span class="flex-none"
                                                            :class="module.level === 'manage' ? 'badge-wsm-green' :
                                                                'badge-wsm-blue'"
                                                            x-text="module.level === 'manage' ? 'Manage' : 'View'"></span>
                                                    </li>
                                                </template>
                                            </ul>
                                            <p x-show="granted(person.info).length > 0 && denied(person.info).length > 0"
                                                class="mt-2 text-[11px] leading-relaxed text-muted">
                                                Tanpa akses: <span
                                                    x-text="denied(person.info).map(m => m.label).join(', ')"></span>.
                                            </p>
                                        </div>

                                        <template x-if="person.info.pages.length">
                                            <div class="mt-4">
                                                <h3 class="text-[11px] font-black uppercase tracking-wide text-muted">
                                                    Halaman khusus Owner</h3>
                                                <ul class="mt-2 grid gap-1">
                                                    <template x-for="page in person.info.pages" :key="page">
                                                        <li class="flex gap-2 text-[13px] leading-relaxed">
                                                            <span class="mt-2 h-1.5 w-1.5 flex-none rounded-full bg-ink"
                                                                aria-hidden="true"></span>
                                                            <span class="min-w-0 wrap-anywhere" x-text="page"></span>
                                                        </li>
                                                    </template>
                                                </ul>
                                            </div>
                                        </template>

                                        <div class="mt-4">
                                            <h3 class="text-[11px] font-black uppercase tracking-wide text-muted">Absensi &
                                                pekerjaan</h3>
                                            <ul class="mt-2 grid gap-1 text-[13px] leading-relaxed">
                                                <li class="flex gap-2">
                                                    <span class="mt-2 h-1.5 w-1.5 flex-none rounded-full bg-ink"
                                                        aria-hidden="true"></span>
                                                    <span class="min-w-0 wrap-anywhere"
                                                        x-text="person.info.attendance"></span>
                                                </li>
                                                <li class="flex gap-2">
                                                    <span class="mt-2 h-1.5 w-1.5 flex-none rounded-full bg-ink"
                                                        aria-hidden="true"></span>
                                                    <span class="min-w-0 wrap-anywhere">
                                                        Kalender bersama di aplikasi karyawan: <span
                                                            x-text="person.info.calendar.toLowerCase()"></span>.
                                                    </span>
                                                </li>
                                                <li class="flex gap-2">
                                                    <span class="mt-2 h-1.5 w-1.5 flex-none rounded-full bg-ink"
                                                        aria-hidden="true"></span>
                                                    <span class="min-w-0">Aplikasi karyawan (absensi, cuti, lembur, tugas
                                                        pribadi) untuk semua akun.</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </template>
                            </section>
                        </template>
                    </div>
                </div>

                <div class="shrink-0 border-t border-line px-5 py-3.5">
                    <button type="button" @click="close()" class="btn-wsm-black w-full py-2.5! text-xs">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection
