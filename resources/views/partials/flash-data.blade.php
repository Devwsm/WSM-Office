{{--
    partials/flash-data.blade.php
    ---------------------------------------------------------------------
    Taruh sekali di tiap layout (sebelum tutup </body> boleh, atau di
    awal body juga boleh — tidak render apa-apa secara visual). Isinya
    dibaca oleh resources/js/alerts.js untuk menampilkan toast/summary
    SweetAlert secara otomatis begitu halaman selesai load.
    ---------------------------------------------------------------------
--}}
<script type="application/json" id="wsm-flash-data">
    {!! json_encode([
        'status' => session('status'),
        'error' => session('error'),
        'warning' => session('warning'),
        'errors' => $errors->any() ? $errors->all() : [],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
