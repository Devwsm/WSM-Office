/**
 * entry-popups.js
 * ---------------------------------------------------------------------
 * Antrean tampil untuk popup informasi preview (README Bab 4.2 no. 8,
 * config/entry_popups.php, App\Support\EntryPopups, partials/entry-popups.blade.php).
 * `x-data="entryPopups({ popups, door, token })"` — `popups` array
 * sudah jadi (server-rendered lewat @js(...)), di sini cuma:
 *  1. Buang popup yang sudah pernah "dibaca" di kunjungan/login ini
 *     (ditandai di sessionStorage, jadi hilang begitu tab ditutup atau
 *     browser di-restart — bukan localStorage yang permanen).
 *  2. Tampilkan sisanya SATU per satu (welcome dulu baru notice, sesuai
 *     urutan dari server) — "next()" dipanggil dari tombol utama, ✕,
 *     klik area gelap, atau Esc (lihat entry-popups.blade.php), jadi
 *     keempatnya sama-sama berarti "sudah dibaca, lanjut ke berikutnya".
 *
 * Kunci sessionStorage gabungan pintu + kunci popup + versi + token sesi
 * (potongan hash session ID Laravel, lihat entry-popups.blade.php) —
 * login ulang atau ganti akun bikin session ID baru (LoginController
 * memanggil session()->regenerate()), jadi otomatis muncul lagi tanpa
 * perlu logika tambahan di sini.
 * ---------------------------------------------------------------------
 */
import Alpine from "alpinejs";

Alpine.data("entryPopups", ({ popups, door, token }) => ({
    queue: [],
    current: null,
    open: false,

    init() {
        this.queue = popups.filter((popup) => !this.seen(popup));
        this.show();
    },

    storageKey(popup) {
        return `wos-entry:${door}:${popup.key}:${popup.version}:${token}`;
    },

    seen(popup) {
        try {
            return sessionStorage.getItem(this.storageKey(popup)) === "1";
        } catch (e) {
            // Private mode dsb — anggap belum dibaca, popup tetap tampil.
            return false;
        }
    },

    markSeen(popup) {
        try {
            sessionStorage.setItem(this.storageKey(popup), "1");
        } catch (e) {
            // Popup tetap ditutup di layar ini, cuma gak diingat lintas halaman.
        }
    },

    show() {
        this.current = this.queue.shift() ?? null;
        this.open = this.current !== null;
    },

    next() {
        if (this.current) {
            this.markSeen(this.current);
        }
        this.show();
    },

    close() {
        this.next();
    },
}));
