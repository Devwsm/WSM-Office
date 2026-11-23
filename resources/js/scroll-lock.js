/**
 * Kunci scroll halaman saat ada modal / overlay layar penuh yang terbuka.
 *
 * Tanpa ini, di HP halaman di belakang modal ikut ter-scroll saat jari
 * menggeser isi modal. Modal di proyek ini semua berpola `fixed inset-0`
 * (x-show Alpine), jadi cukup memantau apakah ada elemen seperti itu yang
 * sedang tampil. Efek CSS-nya (html.wsm-scroll-locked) hanya aktif di
 * bawah 1024px, lihat resources/css/app.css.
 */
const LOCK_CLASS = "wsm-scroll-locked";
let frame = 0;

function isVisible(el) {
    if (el.hasAttribute("x-cloak")) return false;
    const style = window.getComputedStyle(el);
    return (
        style.display !== "none" &&
        style.visibility !== "hidden" &&
        el.getClientRects().length > 0
    );
}

function sync() {
    frame = 0;
    const open = Array.from(document.querySelectorAll(".fixed.inset-0")).some(
        isVisible,
    );
    document.documentElement.classList.toggle(LOCK_CLASS, open);
}

function schedule() {
    if (!frame) frame = window.requestAnimationFrame(sync);
}

function start() {
    new MutationObserver(schedule).observe(document.body, {
        subtree: true,
        childList: true,
        attributes: true,
        attributeFilter: ["style", "class", "hidden", "x-cloak"],
    });
    schedule();
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", start);
} else {
    start();
}
