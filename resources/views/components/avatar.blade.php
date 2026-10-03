{{--
    components/avatar.blade.php
    ---------------------------------------------------------------------
    Avatar user: foto profil kalau ada, kalau tidak inisial (perilaku lama).
    Pakai: <x-avatar :user="$u" class="h-10 w-10 rounded-2xl text-xs" />
    `class` mengatur ukuran/radius/font; warna latar & teks inisial tetap
    bg-ink/text-white seperti sebelumnya.
    ---------------------------------------------------------------------
--}}
@props(['user'])

@if ($user->hasAvatar())
    <span {{ $attributes->class(['grid flex-none place-items-center overflow-hidden bg-[#ddd]']) }}>
        <img src="{{ $user->avatarUrl() }}" alt="Foto {{ $user->name }}" class="h-full w-full object-cover"
            loading="lazy">
    </span>
@else
    <span
        {{ $attributes->class(['grid flex-none place-items-center bg-ink font-black text-white']) }}>{{ $user->initial() }}</span>
@endif
