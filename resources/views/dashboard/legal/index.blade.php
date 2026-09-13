{{--
    dashboard/legal/index.blade.php
    ---------------------------------------------------------------------
    Fase 14 — listing LegalDocument, filter per kategori (Album
    Contracts / Royalty Agreements). Badge "Segera Berakhir" pakai
    LegalDocument::isExpiringSoon() (≤30 hari) — sama pola persis
    dashboard/contracts/index. Tombol tambah/edit/hapus cuma kelihatan
    kalau canManageModule('legal').
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Legal', 'navActive' => 'modules'])

@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3.5">
        <div>
            <a href="{{ route('dashboard.index') }}" class="text-[11px] font-extrabold text-muted">← Dashboard</a>
            <h2 class="mt-2 text-[36px] font-black leading-[0.98] tracking-tight">Legal</h2>
            <p class="mt-1 text-[13px] text-muted">Album Contracts & Royalty Agreements — file, masa berlaku, catatan.</p>
        </div>
        @if (auth()->user()->canManageModule('legal'))
            <a href="{{ route('dashboard.legal.create') }}" class="btn-wsm-black">+ Upload Dokumen</a>
        @endif
    </div>

    <form method="GET" class="mb-4 flex flex-wrap items-center gap-2">
        <select name="category" onchange="this.form.submit()"
            class="rounded-2xl border border-line bg-white px-3.5 py-2 text-[11px] font-bold text-ink">
            <option value="">Semua Kategori</option>
            @foreach (\App\Models\LegalDocument::CATEGORIES as $category)
                <option value="{{ $category }}" @selected($selectedCategory === $category)>
                    {{ $category === 'album' ? 'Album Contracts' : 'Royalty Agreements' }}
                </option>
            @endforeach
        </select>
    </form>

    @if ($documents->isEmpty())
        <div class="card-wsm-white text-center">
            <p class="text-xs text-muted">Belum ada dokumen legal diupload.</p>
        </div>
    @else
        <div class="grid gap-3">
            @foreach ($documents as $document)
                <div class="rounded-wsm border border-line bg-white p-4.5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="badge-wsm-{{ $document->category === 'album' ? 'blue' : 'yellow' }}">{{ $document->categoryLabel() }}</span>
                                @if ($document->isExpiringSoon())
                                    <span class="badge-wsm-yellow">Segera Berakhir</span>
                                @endif
                            </div>
                            <strong class="mt-1 block text-sm">{{ $document->title }}</strong>
                            @if ($document->party)
                                <span class="text-xs text-ink">{{ $document->party }}</span>
                            @endif
                            <br>
                            <a href="{{ asset('storage/' . $document->file_path) }}" target="_blank"
                                class="mt-1 inline-block text-xs font-bold text-ink underline">
                                {{ $document->original_filename }}
                            </a>
                            <span class="block text-[10px] text-muted">
                                {{ $document->formattedSize() }} ·
                                {{ $document->start_date?->translatedFormat('d M Y') ?? '-' }}
                                s/d
                                {{ $document->end_date?->translatedFormat('d M Y') ?? '-' }}
                                · diupload {{ $document->creator->name }}
                            </span>
                            @if ($document->notes)
                                <p class="mt-1.5 text-xs text-ink">{{ $document->notes }}</p>
                            @endif
                        </div>
                    </div>

                    @if (auth()->user()->canManageModule('legal'))
                        <div class="mt-3.5 flex gap-2 border-t border-[#eee8df] pt-3.5">
                            <a href="{{ route('dashboard.legal.edit', $document) }}"
                                class="btn-wsm-white py-2! px-3.5! text-xs">Edit</a>
                            <form method="POST" action="{{ route('dashboard.legal.destroy', $document) }}"
                                data-confirm="{{ $document->title }} akan dihapus permanen."
                                data-confirm-title="Hapus dokumen ini?" data-confirm-button="Ya, hapus"
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

        <div class="mt-5">{{ $documents->links() }}</div>
    @endif
@endsection
