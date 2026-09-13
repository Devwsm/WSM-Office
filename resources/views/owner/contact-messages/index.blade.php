{{--
    owner/contact-messages/index.blade.php
    ---------------------------------------------------------------------
    Fase 1 (susulan, 2026-09-13) — listing pesan dari form Kontak
    publik. Status baru/dibaca ditandai lewat tombol "Tandai Dibaca"
    per-baris, gak ada edit/hapus (read-only selain status baca).
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Pesan Kontak', 'navActive' => 'contact-messages'])

@section('content')
    <div class="mb-6">
        <h2 class="text-[40px] font-black leading-[0.95] tracking-tight">Pesan Kontak</h2>
        <p class="mt-1 text-[15px] text-muted">Pesan yang masuk lewat form Kontak di halaman publik.</p>
    </div>

    @if ($messages->isEmpty())
        <div class="card-wsm-white text-center">
            <p class="text-xs text-muted">Belum ada pesan masuk.</p>
        </div>
    @else
        <div class="grid gap-3.5">
            @foreach ($messages as $message)
                <div class="card-wsm-white">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-extrabold">{{ $message->name }}
                                @if ($message->status === 'baru')
                                    <span class="badge-wsm-blue ml-1.5">Baru</span>
                                @endif
                            </p>
                            <p class="text-[11px] text-muted">{{ $message->email }} ·
                                {{ $message->created_at->translatedFormat('d M Y, H:i') }}</p>
                        </div>
                        @if ($message->status === 'baru')
                            <form method="POST" action="{{ route('owner.contact-messages.markRead', $message) }}">
                                @csrf
                                <button type="submit" class="btn-wsm-white py-2! px-3.5! text-xs">Tandai
                                    Dibaca</button>
                            </form>
                        @endif
                    </div>
                    <p class="mt-3 whitespace-pre-line text-sm text-[#3a362f]">{{ $message->message }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-5">
            {{ $messages->links() }}
        </div>
    @endif
@endsection
