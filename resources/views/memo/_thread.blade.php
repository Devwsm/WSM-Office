{{--
    memo/_thread.blade.php
    ---------------------------------------------------------------------
    Partial reusable buat thread reply 1 memo. Dipakai di 2 tempat:
    employee/home.blade.php (kartu "Info dari Owner") & dashboard/work/
    index.blade.php (sisi manajemen) — makanya action form-nya dioper
    lewat variabel $replyRoute, bukan di-hardcode salah satu route.

    Variabel wajib:
    - $memo        : model Memo (threadMessages HARUS udah di-eager-load)
    - $replyRoute  : string, hasil route('...', $memo)
    ---------------------------------------------------------------------
--}}
<div class="mt-3 border-t border-[#eee8df] pt-3">
    @if ($memo->threadMessages->isEmpty())
        <p class="text-[11px] text-muted">Belum ada reply.</p>
    @else
        <div class="grid gap-2">
            @foreach ($memo->threadMessages as $msg)
                @php $mine = $msg->user_id === auth()->id(); @endphp
                <div
                    class="max-w-[85%] rounded-2xl px-3 py-2 text-[11px] {{ $mine ? 'ml-auto bg-ink text-white' : ($msg->isFromManagement() ? 'bg-[#eef2f6] text-ink' : 'bg-[#f2f0eb] text-ink') }}">
                    <strong class="block text-[10px] {{ $mine ? 'text-white/70' : 'text-muted' }}">
                        {{ $mine ? 'Kamu' : $msg->author->name }}
                        @if (!$mine && $msg->isFromManagement())
                            · Manajemen
                        @endif
                    </strong>
                    <p class="mt-0.5 whitespace-pre-line">{{ $msg->message }}</p>
                    <span class="mt-0.5 block text-[9px] {{ $mine ? 'text-white/50' : 'text-muted/70' }}">
                        {{ $msg->created_at->translatedFormat('d M, H:i') }}
                    </span>
                </div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ $replyRoute }}" class="mt-2.5 flex gap-2">
        @csrf
        <input type="text" name="message" placeholder="Reply memo ini..." required maxlength="2000"
            class="input-wsm flex-1 py-2! text-xs!">
        <button type="submit" class="btn-wsm-black flex-none py-2! px-3.5! text-xs">Reply</button>
    </form>
</div>
