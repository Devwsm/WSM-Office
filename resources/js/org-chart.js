/**
 * org-chart.js
 * ---------------------------------------------------------------------
 * Interaksi halaman Struktur Organisasi (owner/organization/index.blade.php):
 *  - open(id)/close(): pop-up akses saat nama di bagan diklik. Data akses
 *    SUDAH dihitung server (OrganizationController) dan dikirim sebagai
 *    `data`, jadi tidak ada request tambahan. Fokus dipindah ke tombol
 *    tutup saat dibuka dan dikembalikan ke kartu yang diklik saat ditutup.
 *  - fit(): memperkecil bagan desktop (CSS `zoom`) supaya muat selebar
 *    layar; kalau masih terlalu lebar pada skala minimum, bagan bisa digeser
 *    di dalam kotaknya sendiri (halaman tidak ikut melebar).
 * Scroll halaman dikunci otomatis oleh scroll-lock.js (modal = .fixed.inset-0).
 * ---------------------------------------------------------------------
 */
import Alpine from "alpinejs";

const MIN_SCALE = 0.62;

Alpine.data("orgChart", (data) => ({
    nodes: data.nodes,
    access: data.access,
    openId: null,
    lastFocus: null,

    get node() {
        return this.openId ? (this.nodes[this.openId] ?? null) : null;
    },

    /** Orang di kartu yang dibuka + akses akunnya (null = belum punya akun). */
    get people() {
        if (!this.node) return [];
        return this.node.people.map((person) => ({
            ...person,
            info: person.userId ? (this.access[person.userId] ?? null) : null,
        }));
    },

    open(id) {
        this.lastFocus = document.activeElement;
        this.openId = id;
        this.$nextTick(() => {
            this.$refs.body.scrollTop = 0;
            this.$refs.close.focus();
        });
    },

    close() {
        this.openId = null;
        this.$nextTick(() => this.lastFocus?.focus?.());
    },

    granted(info) {
        return info.modules.filter((module) => module.level !== "none");
    },

    denied(info) {
        return info.modules.filter((module) => module.level === "none");
    },

    fit() {
        const wrap = this.$refs.wrap;
        const chart = this.$refs.chart;
        // Bagan desktop disembunyikan di HP (display: none) — tidak ada yang diukur.
        if (!wrap || !chart || wrap.offsetParent === null) return;

        chart.style.zoom = 1;
        const style = window.getComputedStyle(wrap);
        const room =
            wrap.clientWidth -
            parseFloat(style.paddingLeft) -
            parseFloat(style.paddingRight);
        const natural = chart.scrollWidth;
        const scale = natural > 0 ? Math.min(1, room / natural) : 1;
        chart.style.zoom = Math.max(MIN_SCALE, scale);
    },
}));
