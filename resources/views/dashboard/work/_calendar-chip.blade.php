{{--
    dashboard/work/_calendar-chip.blade.php
    Satu chip item/penanda project di sel Timeline Calendar (padanan
    `.cal-event.v19-project-event` prototype). Dipakai untuk chip yang
    langsung tampil maupun yang dilipat di "+N item".
    Input: $item = [id, title, pic, project, progress, focus, color, text, url, done]
    `data-item-id` ada hanya untuk item nyata (penanda ▶/■ project tidak bisa di-drag);
    drag diaktifkan JS di desktop untuk akses work=manage (lihat calendar.blade.php).
--}}
<a href="{{ $item['url'] }}" data-chip @if (!empty($item['id'])) data-item-id="{{ $item['id'] }}" @endif
    class="block overflow-hidden rounded-lg border-l-4 border-black/20 px-1.5 py-1 text-[9px] font-black leading-tight wrap-break-word {{ $item['done'] ? 'opacity-60' : '' }}"
    style="background:{{ $item['color'] }};color:{{ $item['text'] }}"
    title="{{ $item['title'] }}{{ $item['pic'] ? ' · ' . $item['pic'] : '' }}{{ $item['done'] ? ' · Done' : '' }}">
    {{ $item['title'] }}
</a>
