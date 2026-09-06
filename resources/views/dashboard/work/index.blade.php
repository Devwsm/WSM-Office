{{--
    dashboard/work/index.blade.php
    ---------------------------------------------------------------------
    Fase 6b — listing penuh Memo & MoM. Tombol tambah/edit/hapus cuma
    kelihatan kalau canManageModule('work') (dicek di controller/route
    lewat middleware module:work,manage, bukan di view — di sini cuma
    nyembunyiin tombolnya aja).
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Work Control — MoM & Memo', 'navActive' => 'modules'])

@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3.5">
        <div>
            <a href="{{ route('dashboard.index') }}" class="text-[11px] font-extrabold text-muted">← Dashboard</a>
            <h2 class="mt-2 text-[36px] font-black leading-[0.98] tracking-tight">MoM &amp; Memo</h2>
            <p class="mt-1 text-[13px] text-muted">Catatan rapat &amp; pengumuman internal.</p>
        </div>
        @if (auth()->user()->canManageModule('work'))
            <a href="{{ route('dashboard.work.create') }}" class="btn-wsm-black">+ Tambah</a>
        @endif
    </div>

    @if ($memos->isEmpty())
        <div class="card-wsm-white text-center">
            <p class="text-xs text-muted">Belum ada memo atau MoM.</p>
        </div>
    @else
        <div class="grid gap-3">
            @foreach ($memos as $memo)
                <div class="rounded-wsm border border-line bg-white p-4.5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                @if ($memo->pinned)
                                    <span class="text-[10px] font-extrabold text-[#a8873d]">📌 PINNED</span>
                                @endif
                                <span
                                    class="rounded-full bg-[#f2f0eb] px-2.5 py-1 text-[10px] font-extrabold text-[#5e5951]">
                                    {{ $memo->typeLabel() }}
                                </span>
                            </div>
                            <strong class="mt-1.5 block text-sm">{{ $memo->title }}</strong>
                            <span class="text-[10px] text-muted">
                                {{ $memo->creator->name }} · {{ $memo->created_at->translatedFormat('d M Y') }}
                                @if ($memo->type === 'mom' && $memo->meeting_date)
                                    · Rapat {{ $memo->meeting_date->translatedFormat('d M Y') }}
                                @endif
                                @if ($memo->attendees)
                                    · Peserta: {{ $memo->attendees }}
                                @endif
                            </span>
                            <p class="mt-2 whitespace-pre-line text-xs text-ink">{{ $memo->content }}</p>
                        </div>
                    </div>

                    @if (auth()->user()->canManageModule('work'))
                        <div class="mt-3.5 flex gap-2 border-t border-[#eee8df] pt-3.5">
                            <a href="{{ route('dashboard.work.edit', $memo) }}"
                                class="btn-wsm-white py-2! px-3.5! text-xs">Edit</a>
                            <form method="POST" action="{{ route('dashboard.work.destroy', $memo) }}"
                                data-confirm="{{ $memo->title }} akan dihapus permanen."
                                data-confirm-title="Hapus {{ $memo->typeLabel() }} ini?" data-confirm-button="Ya, hapus"
                                data-confirm-danger="1">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-wsm-red py-2! px-3.5! text-xs">Hapus</button>
                            </form>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-5">{{ $memos->links() }}</div>
    @endif
@endsection
