{{--
    dashboard/work/_calendar-chip.blade.php
    Satu chip item/penanda project di sel Timeline Calendar (padanan
    `.cal-event.v19-project-event` prototype). Dipakai untuk chip yang
    langsung tampil maupun yang dilipat di "+N item".
    Input: $item = [title, pic, color, text, url, done]
--}}
<a href="{{ $item['url'] }}"
    class="block overflow-hidden rounded-lg border-l-4 border-black/20 px-1.5 py-1 text-[9px] font-black leading-tight wrap-break-word {{ $item['done'] ? 'opacity-60' : '' }}"
    style="background:{{ $item['color'] }};color:{{ $item['text'] }}"
    title="{{ $item['title'] }}{{ $item['pic'] ? ' · ' . $item['pic'] : '' }}{{ $item['done'] ? ' · Done' : '' }}">
    {{ $item['title'] }}
</a>
