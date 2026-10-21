{{--
    dashboard/work/_calendar-chip.blade.php
    Satu chip item/penanda project di sel Timeline Calendar (padanan
    `.cal-event.v19-project-event` prototype). Dipakai untuk chip yang
    langsung tampil maupun yang dilipat di "+N item".
    Input: $item = [id, title, pic, project, progress, focus, color, text, url, done]
    `data-item-id` ada hanya untuk item nyata (penanda ▶/■ project tidak bisa di-drag);
    drag diaktifkan JS di desktop untuk akses work=manage (lihat calendar.blade.php).
    2026-10-21 — gaya bar kalender Samsung: satu baris, teks di tengah, terpotong (…);
    judul lengkap ada di tooltip dan popup harian.
--}}
<a href="{{ $item['url'] }}" data-chip @if (!empty($item['id'])) data-item-id="{{ $item['id'] }}" @endif
    class="block truncate rounded-[5px] px-1 py-0.5 text-center text-[8px] font-black leading-tight sm:rounded-md sm:px-1.5 sm:text-[10px] {{ $item['done'] ? 'opacity-60' : '' }}"
    style="background:{{ $item['color'] }};color:{{ $item['text'] }}"
    title="{{ $item['title'] }}{{ $item['pic'] ? ' · ' . $item['pic'] : '' }}{{ $item['done'] ? ' · Done' : '' }}">
    {{ $item['title'] }}
</a>
