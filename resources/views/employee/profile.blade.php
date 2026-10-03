{{--
    employee/profile.blade.php
    ---------------------------------------------------------------------
    Isi tab "Profile" di bottom-nav app-mobile. Mengikuti pola
    `renderEmployeeProfile()` di prototype: kartu identitas + foto profil,
    Security (ganti password), dan Personal Colors.

    2026-10-02 (selisih #10):
    - foto profil: tombol "Ganti Foto" (upload langsung ke disk private,
      lihat ProfileController::updateAvatar);
    - ganti password dipindah dari form di halaman ke MODAL yang dibuka
      dari kartu Security. Modal otomatis terbuka lagi kalau validasinya
      gagal (error tidak hilang) atau kalau password masih sementara
      (must_change_password);
    - Personal Colors: 7 warna tampilan pribadi (hanya App Mode milik
      user ini), pratinjau langsung sebelum disimpan.
    ---------------------------------------------------------------------
--}}
@extends('layouts.employee', ['title' => 'Profile', 'navActive' => 'profile'])

@section('content')
    @php
        $themeDefaults = \App\Models\User::THEME_DEFAULTS;
        $themeSaved = $user->themeColors();
        $colorsInit = collect($themeDefaults)
            ->mapWithKeys(fn($default, $key) => [$key => old($key, $themeSaved[$key])])
            ->all();
        $themeFields = [
            'background' => 'Page Background',
            'text' => 'Main Text',
            'primary' => 'Primary / Active',
            'success' => 'Success Badge',
            'attention' => 'Attention Badge',
            'danger' => 'Overdue / Danger',
            'leave' => 'Paid Leave Banner',
        ];
        $passwordHasErrors = $errors->has('current_password') || $errors->has('password');
    @endphp

    <div class="employee-section-head mb-5">
        <h2 class="text-[30px] font-black leading-none tracking-tight">Profile</h2>
        <p class="mt-1.5 text-xs text-muted">Data diri & keamanan akun kamu.</p>
    </div>

    <div class="card-wsm-white mb-3.5">
        <div class="flex items-center gap-3">
            <x-avatar :user="$user" class="h-14 w-14 rounded-2xl text-sm" />
            <div class="min-w-0">
                <strong class="block truncate text-sm">{{ $user->name }}</strong>
                <span class="badge-wsm-gray mt-1 inline-flex capitalize">{{ $user->roleLabel() }}</span>
            </div>
        </div>

        {{-- Foto profil: pilih file -> langsung terkirim. Batas 2 MB dicek di
            browser dulu supaya tidak menunggu upload besar baru ditolak server. --}}
        <div class="mt-3.5" x-data="{ tooBig: false }">
            <form method="POST" action="{{ route('employee.profile.avatar.update') }}" enctype="multipart/form-data"
                class="flex flex-wrap items-center gap-2">
                @csrf
                <label class="btn-wsm-white cursor-pointer px-4 py-2.5 text-xs">
                    {{ $user->hasAvatar() ? 'Ganti Foto' : 'Tambah Foto' }}
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="hidden"
                        @change="
                            tooBig = $el.files[0] && $el.files[0].size > 2097152;
                            if ($el.files[0] && !tooBig) { $el.form.submit(); }
                        ">
                </label>
                @if ($user->hasAvatar())
                    <button type="submit" form="avatar-remove-form"
                        class="px-2 text-xs font-extrabold text-[#a83d35]">Hapus
                        Foto</button>
                @endif
            </form>
            <p x-show="tooBig" x-cloak class="mt-1.5 text-[11px] font-extrabold text-[#a83d35]">Ukuran foto maksimal 2 MB.
                Pilih foto yang lebih kecil.</p>
            @error('photo')
                <p class="mt-1.5 text-[11px] font-extrabold text-[#a83d35]">{{ $message }}</p>
            @enderror
            <p class="mt-1.5 text-[11px] text-muted">JPG, PNG, atau WebP, maksimal 2 MB.</p>
            @if ($user->hasAvatar())
                <form id="avatar-remove-form" method="POST" action="{{ route('employee.profile.avatar.destroy') }}"
                    class="hidden" data-confirm="Foto profil kamu akan dihapus dan diganti inisial."
                    data-confirm-title="Hapus foto?" data-confirm-button="Ya, hapus" data-confirm-danger="1">
                    @csrf
                    @method('DELETE')
                </form>
            @endif
        </div>

        <div class="mt-4 grid gap-3 border-t border-line pt-4">
            <div>
                <span class="field-label-wsm">Email</span>
                <p class="mt-1 text-sm">{{ $user->email }}</p>
            </div>
            @if ($user->job_title)
                <div>
                    <span class="field-label-wsm">Jabatan</span>
                    <p class="mt-1 text-sm">{{ $user->job_title }}</p>
                </div>
            @endif
            @if ($user->division)
                <div>
                    <span class="field-label-wsm">Divisi</span>
                    <p class="mt-1 text-sm">{{ $user->division }}</p>
                </div>
            @endif
            @if ($user->manager)
                <div>
                    <span class="field-label-wsm">Atasan</span>
                    <p class="mt-1 text-sm">{{ $user->manager->name }}</p>
                </div>
            @endif
            <div>
                <span class="field-label-wsm">Sisa Cuti Tahunan</span>
                <p class="mt-1 text-sm">{{ $user->remainingAnnualLeaveDays() }} hari</p>
            </div>
        </div>
    </div>

    @if ($user->must_change_password)
        <div class="mb-3.5 rounded-wsm border border-[#f1c7c2] bg-[#fff0ee] p-4 text-[#a83d35]">
            <p class="text-sm font-extrabold">Ganti password dulu</p>
            <p class="mt-1 text-xs">
                Password kamu masih sementara (hasil reset atau password awal akun). Isi "Password Saat Ini" dengan
                password sementara itu, lalu buat password baru. Halaman lain baru bisa dibuka setelah ini selesai.
            </p>
        </div>
    @endif

    {{-- Security — tombol pembuka modal ganti password. `open` mulai true kalau
        server baru saja menolak isian (error harus tetap kelihatan) atau
        password masih sementara. --}}
    <div class="card-wsm-white mb-3.5" x-data="{ open: {{ $passwordHasErrors || $user->must_change_password ? 'true' : 'false' }} }" @keydown.escape.window="open = false">
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
                <p class="text-sm font-extrabold">Security</p>
                <p class="mt-0.5 text-xs text-muted">Minimal 8 karakter. Kamu tetap login setelah ganti password.</p>
            </div>
            <button type="button" @click="open = true" class="btn-wsm-black flex-none px-4 py-2.5 text-xs">Ganti
                Password</button>
        </div>

        <div x-show="open" x-cloak
            class="fixed inset-0 z-50 grid place-items-end bg-black/40 p-0 sm:place-items-center sm:p-4"
            x-effect="if (open) $nextTick(() => $refs.current && $refs.current.focus())" role="dialog" aria-modal="true"
            aria-labelledby="password-modal-title">
            <div @click.outside="open = false"
                class="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-t-4xl bg-cream p-5 sm:rounded-4xl">
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <div class="text-[10px] font-extrabold uppercase text-muted">Security</div>
                        <h3 id="password-modal-title" class="text-lg font-black">Ganti Password</h3>
                        <p class="text-xs text-muted">Minimal 8 karakter dan harus berbeda dari password saat ini.</p>
                    </div>
                    <button type="button" @click="open = false" aria-label="Tutup"
                        class="grid h-9 w-9 flex-none place-items-center rounded-2xl bg-[#ece7dd]">✕</button>
                </div>

                <form method="POST" action="{{ route('employee.profile.password') }}" class="grid gap-3">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label class="field-label-wsm">Password Saat Ini</label>
                        <input type="password" name="current_password" x-ref="current" autocomplete="current-password"
                            class="input-wsm mt-1.5" required>
                        @error('current_password')
                            <p class="mt-1 text-[11px] font-extrabold text-[#a83d35]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="field-label-wsm">Password Baru</label>
                        <input type="password" name="password" autocomplete="new-password" class="input-wsm mt-1.5" required
                            minlength="8">
                        @error('password')
                            <p class="mt-1 text-[11px] font-extrabold text-[#a83d35]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="field-label-wsm">Konfirmasi Password Baru</label>
                        <input type="password" name="password_confirmation" autocomplete="new-password"
                            class="input-wsm mt-1.5" required minlength="8">
                    </div>

                    <div class="mt-1.5 grid grid-cols-2 gap-2">
                        <button type="button" @click="open = false" class="btn-wsm-white">Batal</button>
                        <button type="submit" class="btn-wsm-black">Simpan Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Personal Colors — padanan employeeThemeSettingsMarkupV22 di prototype.
        Teks hex & kotak warna saling sinkron; pratinjau (dan halaman ini)
        berubah langsung, tapi baru tersimpan setelah "Save My Colors".
        "Reset Default" langsung menghapus warna tersimpan (seperti prototype). --}}
    <div class="card-wsm-white" x-data="{
        colors: @js($colorsInit),
        vars: { background: '--color-cream', primary: '--color-ink', success: '--color-brand-green', attention: '--color-brand-yellow', danger: '--color-brand-red', leave: '--color-brand-lime' },
        valid(v) { return /^#[0-9a-fA-F]{6}$/.test(v || ''); },
        apply() {
            const b = document.body;
            for (const [key, cssVar] of Object.entries(this.vars)) {
                if (this.valid(this.colors[key])) b.style.setProperty(cssVar, this.colors[key]);
            }
            if (this.valid(this.colors.text)) b.style.color = this.colors.text;
        },
        init() { this.$watch('colors', () => this.apply()); },
    }">
        <p class="text-sm font-extrabold">Personal Colors</p>
        <p class="mb-4 mt-0.5 text-xs text-muted">Customize hanya tampilan dashboard kamu sendiri.</p>

        <form method="POST" action="{{ route('employee.profile.theme.update') }}" class="grid gap-3">
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-2 gap-3">
                @foreach ($themeFields as $key => $label)
                    <div>
                        <label class="field-label-wsm">{{ $label }}</label>
                        <div class="mt-1.5 flex items-center gap-1.5">
                            <input type="text" name="{{ $key }}" x-model="colors.{{ $key }}"
                                maxlength="7" spellcheck="false" class="input-wsm min-w-0 flex-1 px-3! py-2.5! text-xs"
                                required>
                            <input type="color" x-model="colors.{{ $key }}"
                                aria-label="Pilih warna {{ $label }}"
                                class="h-10 w-10 flex-none cursor-pointer rounded-xl border border-line bg-white p-1">
                        </div>
                        @error($key)
                            <p class="mt-1 text-[11px] font-extrabold text-[#a83d35]">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach
            </div>

            <div class="flex flex-wrap items-center gap-2 rounded-wsm border border-line p-3 text-[10px] font-black"
                :style="`background:${colors.background};color:${colors.text}`">
                <span class="rounded-full px-3 py-1.5 text-white" :style="`background:${colors.primary}`">Primary</span>
                <span class="rounded-full px-3 py-1.5 text-[#111]" :style="`background:${colors.success}`">Done</span>
                <span class="rounded-full px-3 py-1.5 text-[#111]" :style="`background:${colors.attention}`">Follow
                    Up</span>
                <span class="rounded-full px-3 py-1.5 text-white" :style="`background:${colors.danger}`">Overdue</span>
                <span class="rounded-full px-3 py-1.5 text-[#111]" :style="`background:${colors.leave}`">Paid Leave</span>
                <span>Text Preview</span>
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="submit" class="btn-wsm-black">Save My Colors</button>
                <button type="submit" form="theme-reset-form" class="btn-wsm-white">Reset Default</button>
            </div>
        </form>
        <form id="theme-reset-form" method="POST" action="{{ route('employee.profile.theme.reset') }}" class="hidden"
            data-confirm="Warna tampilan kamu dikembalikan ke bawaan WSM." data-confirm-title="Reset warna?"
            data-confirm-button="Ya, reset">
            @csrf
            @method('DELETE')
        </form>
    </div>
@endsection
