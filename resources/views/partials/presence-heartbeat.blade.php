{{--
    partials/presence-heartbeat.blade.php
    ---------------------------------------------------------------------
    Heartbeat Monitor Login. Mengirim ping hanya kalau tab terlihat DAN ada
    interaksi (klik/ketik/scroll/sentuh) dalam activity_window_seconds
    terakhir — jadi ping tidak menjaga sesi login tetap hidup kalau
    karyawan sedang tidak memakai aplikasi. Dipasang di layouts.app &
    layouts.employee.
    ---------------------------------------------------------------------
--}}
@auth
    <script>
        (function() {
            var url = @js(route('presence.ping'));
            var every = {{ (int) config('presence.heartbeat_seconds', 60) }} * 1000;
            var windowMs = {{ (int) config('presence.activity_window_seconds', 120) }} * 1000;
            var meta = document.querySelector('meta[name=csrf-token]');
            var lastActive = Date.now();
            var lastPing = 0;

            function mark() {
                lastActive = Date.now();
            }
            ['pointerdown', 'keydown', 'scroll', 'touchstart'].forEach(function(evt) {
                window.addEventListener(evt, mark, {
                    passive: true
                });
            });

            function ping() {
                var now = Date.now();
                if (document.hidden || now - lastActive > windowMs || now - lastPing < every - 1000) return;
                lastPing = now;
                fetch(url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    keepalive: true,
                    headers: {
                        'X-CSRF-TOKEN': meta ? meta.content : '',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                }).catch(function() {});
            }

            setInterval(ping, every);
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) ping();
            });
        })
        ();
    </script>
@endauth
