/**
 * public-motion.js — animasi halus untuk halaman publik (Beranda).
 * ---------------------------------------------------------------------
 * Dua hal saja, keduanya memakai kurva ease-in-out:
 *   1. [data-reveal]   elemen memudar + naik pelan saat masuk layar.
 *                      [data-reveal-delay="ms"] memberi jeda antar saudara.
 *   2. [data-countup]  angka menghitung dari 0 ke nilai akhir.
 *
 * Aman dipakai di semua halaman: kalau tidak ada elemen yang dimaksud,
 * file ini tidak melakukan apa-apa. Kalau perangkat memilih "kurangi
 * gerakan" atau browser tidak punya IntersectionObserver, semuanya
 * langsung ditampilkan tanpa animasi. Sebelum JS jalan konten tetap
 * terlihat penuh (elemen baru disembunyikan oleh CSS `.js [data-reveal]`,
 * dan kelas `js` dipasang layout publik).
 * ---------------------------------------------------------------------
 */
const REVEAL_MS = 900;
const COUNT_MS = 1600;

const easeInOutCubic = (t) =>
    t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;

function reveal(el) {
    el.classList.add("is-visible");

    // Setelah animasi masuk selesai, lepas atribut supaya transisi hover
    // milik elemen itu sendiri (mis. kartu yang terangkat) tidak ikut tertunda.
    const delay = parseInt(el.dataset.revealDelay || "0", 10) || 0;
    setTimeout(
        () => el.removeAttribute("data-reveal"),
        delay + REVEAL_MS + 100,
    );
}

function countUp(el, delay) {
    const target = parseFloat(el.dataset.countup);
    const decimals = parseInt(el.dataset.decimals || "0", 10) || 0;

    if (!Number.isFinite(target)) {
        return;
    }

    setTimeout(() => {
        const start = performance.now();

        const tick = (now) => {
            const progress = Math.min(1, (now - start) / COUNT_MS);
            el.textContent = (target * easeInOutCubic(progress)).toFixed(
                decimals,
            );

            if (progress < 1) {
                requestAnimationFrame(tick);
            }
        };

        requestAnimationFrame(tick);
    }, delay);
}

function init() {
    const revealEls = document.querySelectorAll("[data-reveal]");
    const countEls = document.querySelectorAll("[data-countup]");

    if (!revealEls.length && !countEls.length) {
        return;
    }

    const reduceMotion = window.matchMedia(
        "(prefers-reduced-motion: reduce)",
    ).matches;

    if (reduceMotion || !("IntersectionObserver" in window)) {
        revealEls.forEach((el) => el.classList.add("is-visible"));
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                observer.unobserve(entry.target);

                if (entry.target.hasAttribute("data-countup")) {
                    countUp(entry.target, entry.target._countDelay || 0);
                } else {
                    reveal(entry.target);
                }
            });
        },
        { threshold: 0.15, rootMargin: "0px 0px -6% 0px" },
    );

    revealEls.forEach((el) => {
        // Jeda antar saudara: atribut data-reveal-delay -> variabel CSS.
        el.style.setProperty(
            "--reveal-delay",
            `${parseInt(el.dataset.revealDelay || "0", 10) || 0}ms`,
        );
        observer.observe(el);
    });

    countEls.forEach((el) => {
        const holder = el.closest("[data-reveal-delay]");

        el._countDelay = holder
            ? parseInt(holder.dataset.revealDelay || "0", 10) || 0
            : 0;
        el.textContent = (0).toFixed(
            parseInt(el.dataset.decimals || "0", 10) || 0,
        );
        observer.observe(el);
    });
}

init();
