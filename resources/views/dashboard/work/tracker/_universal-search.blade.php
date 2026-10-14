{{--
    Pencarian universal Work Tracker (2026-10-14)
    ---------------------------------------------------------------------
    Kolom cari tunggal di atas daftar project. Menyaring REALTIME semua task
    yang sudah tampil di halaman (judul, catatan, PIC, section, project,
    progress, prioritas, fokus, link, nomor) tanpa reload. Bahan cari tiap
    baris ada di atribut `data-search` (dibuat server dari data yang boleh
    dilihat pengguna), jadi section terkunci tidak pernah bisa ikut ketemu.

    - Beberapa kata = semua kata harus cocok (urutan bebas); huruf besar/kecil
      dan aksen diabaikan.
    - Project & section yang cocok otomatis terbuka, yang tidak cocok
      disembunyikan. Pencarian dikosongkan -> keadaan buka/tutup kembali
      seperti sebelum mencari.
    - Berjalan di atas filter server (Project/PIC/Progress/Focus): filter
      mempersempit data dulu, pencarian menyaring hasilnya.
    - Tombol "/" memfokuskan kolom cari, Esc mengosongkannya.
    ---------------------------------------------------------------------
--}}
<style>
    /* Kelas layout (grid/flex) menimpa atribut [hidden] bawaan browser, jadi dipaksa. */
    tr[data-search][hidden],
    details.wt-section[hidden],
    article[data-wt-card][hidden],
    #wtSearchEmpty[hidden] {
        display: none !important;
    }

    #wtSearch::-webkit-search-cancel-button {
        display: none;
    }
</style>

<script>
    (function() {
        const input = document.getElementById('wtSearch');
        if (!input) return;

        const clearBtn = document.getElementById('wtSearchClear');
        const info = document.getElementById('wtSearchInfo');
        const empty = document.getElementById('wtSearchEmpty');
        const norm = (t) => String(t ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();

        const rows = [...document.querySelectorAll('tr[data-search]')];
        rows.forEach((r) => {
            r._wtKey = norm(r.dataset.search);
        });
        const sections = [...document.querySelectorAll('details.wt-section')];
        const cards = [...document.querySelectorAll('details.wt-card')];

        let saved = null; // keadaan buka/tutup sebelum mulai mencari
        let timer = null;

        function restore() {
            rows.forEach((r) => r.hidden = false);
            sections.forEach((d) => d.hidden = false);
            cards.forEach((d) => d.closest('article').hidden = false);
            if (saved) {
                cards.forEach((d, i) => d.open = saved.cards[i]);
                sections.forEach((d, i) => d.open = saved.sections[i]);
                saved = null;
            }
            info.textContent = '';
            empty.hidden = true;
        }

        function apply() {
            const raw = input.value;
            const tokens = norm(raw).split(/\s+/).filter(Boolean);
            clearBtn.hidden = raw === '';

            if (tokens.length === 0) {
                restore();
                return;
            }

            if (!saved) {
                saved = {
                    cards: cards.map((d) => d.open),
                    sections: sections.map((d) => d.open),
                };
            }

            let hits = 0;
            rows.forEach((r) => {
                const match = tokens.every((t) => r._wtKey.includes(t));
                r.hidden = !match;
                if (match) hits++;
            });

            sections.forEach((sec) => {
                const any = [...sec.querySelectorAll('tr[data-search]')].some((r) => !r.hidden);
                sec.hidden = !any;
                if (any) sec.open = true;
            });

            cards.forEach((card) => {
                const any = card.querySelector('details.wt-section:not([hidden])') !== null;
                card.closest('article').hidden = !any;
                if (any) card.open = true;
            });

            info.textContent = hits > 0 ? hits + ' task cocok' : 'Tidak ada yang cocok';
            empty.hidden = hits > 0;
        }

        input.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(apply, 60);
        });

        clearBtn.addEventListener('click', () => {
            input.value = '';
            apply();
            input.focus();
        });

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && input.value !== '') {
                e.preventDefault();
                input.value = '';
                apply();
            }
        });

        // "/" = lompat ke kolom cari (kecuali sedang mengetik di kolom lain).
        document.addEventListener('keydown', (e) => {
            if (e.key !== '/' || e.ctrlKey || e.metaKey || e.altKey) return;
            const t = e.target;
            if (t && (t.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(t.tagName))) return;
            e.preventDefault();
            input.focus();
            input.select();
        });

        // Kembali dari cache browser (tombol Back) dengan isi kolom yang masih terisi.
        window.addEventListener('pageshow', () => {
            if (input.value !== '') apply();
        });
    })();
</script>
