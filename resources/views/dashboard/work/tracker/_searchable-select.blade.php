{{--
    Dropdown dengan pencarian realtime (2026-10-14, revisi 2026-10-20)
    ---------------------------------------------------------------------
    Peningkatan progresif untuk setiap <select data-searchable>: <select>
    asli TETAP jadi sumber nilai (form submit, required, onchange="..." dan
    kode lama yang membaca/menulis `.value` semuanya tetap jalan). Yang
    ditambahkan hanya tombol pengganti tampilan + 1 panel pencarian bersama
    (satu elemen untuk seluruh halaman, jadi ratusan baris item tetap ringan).

    - Ketik untuk menyaring langsung (tanpa tombol cari); huruf besar/kecil
      dan aksen diabaikan; beberapa kata = semua kata harus cocok.
    - Panah atas/bawah + Enter memilih, Esc menutup (tidak ikut menutup modal).
    - Opsi `disabled` (mis. PIC yang sudah dipilih di kolom lain) tampil
      redup dan tidak bisa dipilih.
    - Memilih memicu event `change` biasa, jadi filter tetap auto-submit dan
      edit PIC per baris tetap tersimpan lewat wtSaveField().

    Mode CHECKLIST (2026-10-20): `<select multiple data-searchable>` tampil
    sebagai daftar bercentang — beberapa pilihan sekaligus (dipakai filter
    Work Tracker). Panel tetap terbuka saat mencentang; pilihan baru
    diterapkan (event `change` SEKALI, mis. submit filter) saat panel ditutup:
    klik di luar / Tab / tombol "Terapkan". Esc = batal. Tombol "Reset" =
    kosongkan semua pilihan lalu terapkan. Tanpa pilihan = "semua" (label
    diambil dari data-all-label).

    Atribut opsional: data-search-placeholder="Cari ...", data-all-label="Semua ...".

    Tombol pengganti SELALU selebar kolomnya (`width:100%`): elemen <button>
    secara bawaan menyusut mengikuti isi, beda dengan <select> — itu penyebab
    dropdown di form Tambah Task tampak sempit dan tidak rapi.
    ---------------------------------------------------------------------
--}}
<style>
    .wt-ss {
        position: relative;
        display: block;
        min-width: 0;
        max-width: 100%;
    }

    /* Kelas layout menimpa atribut [hidden] bawaan browser, jadi dipaksa. */
    .wt-ss[hidden] {
        display: none;
    }

    /* Setelah dibungkus, select asli (termasuk <select multiple>) tidak ikut menentukan tinggi/tampilan. */
    .wt-ss>select[multiple] {
        height: 100%;
        overflow: hidden;
    }

    /* Select asli disembunyikan secara visual tapi tetap ada, supaya validasi `required` browser tetap bekerja. */
    .wt-ss>select {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        pointer-events: none;
    }

    .wt-ss-btn {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .4rem;
        width: 100%;
        min-width: 0;
        text-align: left;
        cursor: pointer;
    }

    .wt-ss-btn>.wt-ss-label {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .wt-ss-btn[data-empty="1"]>.wt-ss-label {
        opacity: .65;
    }

    .wt-ss-btn>.wt-ss-chevron {
        flex: none;
        font-size: .8em;
        opacity: .6;
    }

    /* Tombol yang sedang terbuka: border sedikit lebih tegas, satu keluarga warna dengan panelnya. */
    .wt-ss-btn[aria-expanded="true"] {
        border-color: #cfc7b8;
        box-shadow: 0 0 0 3px rgba(23, 19, 10, .06);
    }

    .wt-ss-btn:disabled {
        cursor: not-allowed;
        opacity: .6;
    }

    /* Panel & kolom cari memakai border yang sama dengan filter (border-line, sudut rounded-2xl). */
    #wtSsPanel {
        position: fixed;
        z-index: 90;
        display: none;
        flex-direction: column;
        gap: .35rem;
        padding: .45rem;
        border: 1px solid var(--color-line, #e5dfd5);
        border-radius: 1rem;
        background: #fff;
        box-shadow: 0 14px 38px rgba(23, 19, 10, .14);
        font-size: 12px;
    }

    #wtSsPanel.is-open {
        display: flex;
    }

    #wtSsInput {
        width: 100%;
        border: 1px solid var(--color-line, #e5dfd5);
        border-radius: 1rem;
        background: #fff;
        padding: .45rem .8rem;
        font-size: 12px;
        font-weight: 700;
        outline: none;
        box-shadow: none;
    }

    #wtSsInput:focus {
        border-color: #cfc7b8;
        box-shadow: 0 0 0 3px rgba(23, 19, 10, .06);
    }

    #wtSsList {
        margin: 0;
        padding: 0;
        list-style: none;
        overflow-y: auto;
        overscroll-behavior: contain;
    }

    #wtSsList li {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
        padding: .42rem .6rem;
        border-radius: .75rem;
        font-weight: 700;
        cursor: pointer;
    }

    #wtSsList li>span:not(.wt-ss-box) {
        min-width: 0;
        overflow-wrap: anywhere;
    }

    #wtSsList li[data-active="1"] {
        background: #f1ead9;
    }

    /* Mode satu pilihan: tanda centang di kanan. */
    #wtSsPanel:not(.is-multi) #wtSsList li[aria-selected="true"]::after {
        content: "✓";
        font-weight: 900;
        color: #176c37;
    }

    #wtSsList li[aria-disabled="true"] {
        opacity: .4;
        cursor: not-allowed;
    }

    #wtSsList li.wt-ss-empty {
        cursor: default;
        font-weight: 600;
        opacity: .6;
    }

    /* Mode checklist: kotak centang di kiri tiap opsi + baris kaki (jumlah, Reset, Terapkan). */
    #wtSsPanel.is-multi #wtSsList li {
        justify-content: flex-start;
    }

    .wt-ss-box {
        flex: none;
        display: grid;
        place-items: center;
        width: 1.05rem;
        height: 1.05rem;
        border: 1.5px solid #bdb5a6;
        border-radius: .35rem;
        background: #fff;
        font-size: .72rem;
        font-weight: 900;
        line-height: 1;
        color: #fff;
    }

    #wtSsList li[aria-selected="true"] .wt-ss-box {
        border-color: #17130a;
        background: #17130a;
    }

    #wtSsList li[aria-selected="true"] .wt-ss-box::after {
        content: "✓";
    }

    #wtSsFoot {
        display: none;
        align-items: center;
        gap: .4rem;
        padding-top: .2rem;
        border-top: 1px solid var(--color-line, #e5dfd5);
    }

    #wtSsPanel.is-multi #wtSsFoot {
        display: flex;
    }

    #wtSsCount {
        flex: 1;
        min-width: 0;
        white-space: nowrap;
        font-size: 11px;
        font-weight: 800;
        color: #8b867e;
    }

    #wtSsFoot button {
        border: 1px solid var(--color-line, #e5dfd5);
        border-radius: 1rem;
        background: #fff;
        padding: .35rem .8rem;
        font-size: 11px;
        font-weight: 800;
        cursor: pointer;
    }

    #wtSsFoot button#wtSsApply {
        border-color: #17130a;
        background: #17130a;
        color: #fff;
    }

    @media (max-width: 640px) {

        /* 16px mencegah iOS zoom otomatis saat kolom cari difokuskan. */
        #wtSsInput {
            font-size: 16px;
        }

        #wtSsList li {
            padding: .6rem .7rem;
            font-size: 13px;
        }

        #wtSsFoot button {
            padding: .5rem .95rem;
            font-size: 12px;
        }
    }
</style>

<script>
    (function() {
        const valueDesc = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'value');
        const norm = (t) => String(t ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();

        let panel, input, list, foot, countEl;
        let openSelect = null; // <select> yang sedang dibuka
        let openBtn = null;
        let openMulti = false;
        let items = []; // opsi dari select yang sedang dibuka
        let shown = []; // hasil saringan
        let active = -1;
        let openWidth = 0;

        function ensurePanel() {
            if (panel) return;
            panel = document.createElement('div');
            panel.id = 'wtSsPanel';
            panel.setAttribute('role', 'dialog');
            panel.setAttribute('aria-label', 'Pilih dengan pencarian');

            input = document.createElement('input');
            input.id = 'wtSsInput';
            input.type = 'text';
            input.autocomplete = 'off';
            input.spellcheck = false;
            input.setAttribute('role', 'combobox');
            input.setAttribute('aria-controls', 'wtSsList');
            input.setAttribute('aria-autocomplete', 'list');

            list = document.createElement('ul');
            list.id = 'wtSsList';
            list.setAttribute('role', 'listbox');

            foot = document.createElement('div');
            foot.id = 'wtSsFoot';
            countEl = document.createElement('span');
            countEl.id = 'wtSsCount';
            const resetBtn = document.createElement('button');
            resetBtn.type = 'button';
            resetBtn.id = 'wtSsReset';
            resetBtn.textContent = 'Reset';
            const applyBtn = document.createElement('button');
            applyBtn.type = 'button';
            applyBtn.id = 'wtSsApply';
            applyBtn.textContent = 'Terapkan';
            foot.append(countEl, resetBtn, applyBtn);

            panel.append(input, list, foot);
            document.body.appendChild(panel);

            input.addEventListener('input', () => render(input.value));
            input.addEventListener('keydown', onKey);

            // Modal task memakai @click.outside & Esc di level window: klik/tombol di panel (yang ada di <body>,
            // di luar modal) tidak boleh sampai ke sana, kalau tidak modal ikut tertutup.
            panel.addEventListener('click', (e) => e.stopPropagation());
            panel.addEventListener('keydown', (e) => e.stopPropagation());

            list.addEventListener('mousemove', (e) => {
                const li = e.target.closest('li[data-idx]');
                if (li) setActive(Number(li.dataset.idx), false);
            });
            list.addEventListener('click', (e) => {
                const li = e.target.closest('li[data-idx]');
                if (li) pick(shown[Number(li.dataset.idx)], li);
            });

            resetBtn.addEventListener('click', () => {
                items.forEach((it) => it.selected = false);
                close(true, 'apply');
            });
            applyBtn.addEventListener('click', () => close(true, 'apply'));

            // Klik di luar panel & tombol aktif -> tutup (checklist: terapkan pilihan).
            document.addEventListener('pointerdown', (e) => {
                if (!openSelect) return;
                if (panel.contains(e.target) || (openBtn && openBtn.contains(e.target))) return;
                close(false, 'apply');
            }, true);

            // Scroll/resize: ikuti posisi tombol (keyboard HP bisa memicu resize/scroll), tutup bila tombol hilang dari layar.
            const follow = () => {
                if (!openSelect) return;
                const r = openBtn.getBoundingClientRect();
                if (r.bottom < 0 || r.top > window.innerHeight || (window.innerWidth !== openWidth)) {
                    close(false, 'cancel');
                    return;
                }
                position();
            };
            window.addEventListener('scroll', (e) => {
                if (openSelect && !panel.contains(e.target)) follow();
            }, true);
            window.addEventListener('resize', follow);
        }

        function selectedTexts(select) {
            return [...select.options].filter((o) => o.selected && o.value !== '').map((o) => o.textContent.trim());
        }

        function labelOf(select) {
            if (select.multiple) {
                const picked = selectedTexts(select);
                if (picked.length === 0) return select.dataset.allLabel || 'Semua';
                return picked.length === 1 ? picked[0] : picked[0] + ' +' + (picked.length - 1);
            }
            const opt = select.selectedOptions && select.selectedOptions[0];
            return opt ? opt.textContent.trim() : '';
        }

        function refresh(select) {
            const btn = select._wtSsBtn;
            if (!btn) return;
            btn.querySelector('.wt-ss-label').textContent = labelOf(select);
            btn.dataset.empty = (select.multiple ? selectedTexts(select).length === 0 : valueDesc.get.call(
                select) === '') ? '1' : '0';
            btn.disabled = select.disabled;
        }

        function enhance(select) {
            if (select._wtSsBtn) return;

            const wrap = document.createElement('div');
            wrap.className = 'wt-ss';
            select.parentNode.insertBefore(wrap, select);
            wrap.appendChild(select);

            const btn = document.createElement('button');
            btn.type = 'button';
            // Tampilan tombol = tampilan select aslinya (kelas Tailwind yang sama), jadi tidak ada perubahan desain.
            btn.className = 'wt-ss-btn ' + select.className;
            btn.setAttribute('aria-haspopup', 'listbox');
            btn.setAttribute('aria-expanded', 'false');
            const aria = select.getAttribute('aria-label');
            if (aria) btn.setAttribute('aria-label', aria);

            const label = document.createElement('span');
            label.className = 'wt-ss-label';
            const chev = document.createElement('span');
            chev.className = 'wt-ss-chevron';
            chev.setAttribute('aria-hidden', 'true');
            chev.textContent = '⌄';
            btn.append(label, chev);
            wrap.appendChild(btn);

            select.tabIndex = -1;
            select.setAttribute('aria-hidden', 'true');
            select._wtSsBtn = btn;

            // Kode lama menulis `select.value = ...` (form.reset, isi form Edit, revert gagal simpan) tanpa event:
            // setter ini menjaga label tombol selalu sama dengan nilai select. (Checklist tidak memakai `.value`.)
            if (!select.multiple) {
                Object.defineProperty(select, 'value', {
                    configurable: true,
                    get() {
                        return valueDesc.get.call(this);
                    },
                    set(v) {
                        valueDesc.set.call(this, v);
                        refresh(this);
                    },
                });
            }
            select.addEventListener('change', () => refresh(select));
            if (select.form) select.form.addEventListener('reset', () => setTimeout(() => refresh(select), 0));
            // Validasi `required` memfokuskan select asli -> teruskan ke tombol yang terlihat.
            select.addEventListener('focus', () => btn.focus());

            // Kode lain menyembunyikan select (class `hidden`) atau menonaktifkannya (`disabled`):
            // tombol pengganti (di dalam pembungkus) harus ikut, kalau tidak ia tertinggal di layar.
            new MutationObserver(() => {
                wrap.hidden = select.classList.contains('hidden');
                refresh(select);
            }).observe(select, {
                attributes: true,
                attributeFilter: ['class', 'disabled']
            });
            wrap.hidden = select.classList.contains('hidden');

            btn.addEventListener('click', () => {
                if (openSelect === select) {
                    close(true, 'apply');
                } else {
                    open(select, btn);
                }
            });
            btn.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                    e.preventDefault();
                    open(select, btn);
                }
            });

            refresh(select);
        }

        function open(select, btn) {
            ensurePanel();
            if (openSelect) close(false, 'apply');
            if (select.disabled) return;

            openSelect = select;
            openBtn = btn;
            openMulti = select.multiple;
            openWidth = window.innerWidth;
            items = [...select.options]
                .filter((o) => !(o.value === '' && o.disabled)) // placeholder "Pilih ..." bukan pilihan
                .map((o) => ({
                    value: o.value,
                    text: o.textContent.trim(),
                    disabled: o.disabled,
                    selected: openMulti ? o.selected : o.value === valueDesc.get.call(select),
                    key: norm(o.textContent),
                }));

            input.placeholder = select.dataset.searchPlaceholder || 'Cari…';
            input.value = '';
            btn.setAttribute('aria-expanded', 'true');
            panel.classList.toggle('is-multi', openMulti);
            panel.classList.add('is-open');
            render('');
            updateCount();
            position();
            input.focus({
                preventScroll: true
            });
        }

        // mode: 'apply' (terapkan pilihan checklist) | 'cancel' (buang). Mode satu pilihan tidak terpengaruh.
        function close(refocus, mode) {
            if (!openSelect) return;
            const select = openSelect;
            const btn = openBtn;
            const wasMulti = openMulti;
            const snapshot = items;

            btn.setAttribute('aria-expanded', 'false');
            panel.classList.remove('is-open');
            openSelect = null;
            openBtn = null;
            openMulti = false;

            if (wasMulti && mode === 'apply') {
                const wanted = new Set(snapshot.filter((it) => it.selected).map((it) => it.value));
                const before = [...select.options].filter((o) => o.selected).map((o) => o.value).sort().join(
                    '\u0001');
                [...select.options].forEach((o) => o.selected = wanted.has(o.value));
                refresh(select);
                const after = [...select.options].filter((o) => o.selected).map((o) => o.value).sort().join(
                    '\u0001');
                if (before !== after) {
                    select.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                }
            }

            if (refocus) btn.focus({
                preventScroll: true
            });
        }

        function render(query) {
            const tokens = norm(query).split(/\s+/).filter(Boolean);
            shown = items.filter((it) => tokens.every((t) => it.key.includes(t)));
            list.textContent = '';

            if (shown.length === 0) {
                const li = document.createElement('li');
                li.className = 'wt-ss-empty';
                li.textContent = 'Tidak ada hasil untuk “' + query.trim() + '”';
                list.appendChild(li);
                active = -1;
                return;
            }

            shown.forEach((it, i) => {
                const li = document.createElement('li');
                li.setAttribute('role', 'option');
                li.dataset.idx = String(i);
                if (it.selected) li.setAttribute('aria-selected', 'true');
                if (it.disabled) li.setAttribute('aria-disabled', 'true');
                if (openMulti) {
                    const box = document.createElement('span');
                    box.className = 'wt-ss-box';
                    box.setAttribute('aria-hidden', 'true');
                    li.appendChild(box);
                }
                const span = document.createElement('span');
                span.textContent = it.text; // textContent: nama dari database, bukan HTML
                li.appendChild(span);
                list.appendChild(li);
            });

            // Sedang mengetik -> sorot hasil teratas yang bisa dipilih; kalau belum, sorot yang terpilih saat ini.
            let start = tokens.length ? shown.findIndex((it) => !it.disabled) : shown.findIndex((it) => it
                .selected);
            if (start < 0) start = shown.findIndex((it) => !it.disabled);
            setActive(start, true);
        }

        function updateCount() {
            if (!openMulti) return;
            const n = items.filter((it) => it.selected).length;
            countEl.textContent = n === 0 ? 'Semua' : n + ' dipilih';
        }

        function setActive(idx, scroll) {
            active = idx;
            list.querySelectorAll('li[data-idx]').forEach((li) => {
                li.dataset.active = Number(li.dataset.idx) === idx ? '1' : '0';
            });
            if (scroll && idx >= 0) {
                const li = list.querySelector('li[data-idx="' + idx + '"]');
                if (li) li.scrollIntoView({
                    block: 'nearest'
                });
            }
        }

        function move(step) {
            if (!shown.length) return;
            let i = active;
            for (let n = 0; n < shown.length; n++) {
                i = (i + step + shown.length) % shown.length;
                if (!shown[i].disabled) {
                    setActive(i, true);
                    return;
                }
            }
        }

        function onKey(e) {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                move(1);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                move(-1);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (active >= 0) pick(shown[active], list.querySelector('li[data-idx="' + active + '"]'));
            } else if (e.key === 'Escape') {
                e.preventDefault();
                close(true, 'cancel');
            } else if (e.key === 'Tab') {
                close(false, 'apply');
            }
        }

        function pick(item, li) {
            if (!item || item.disabled || !openSelect) return;

            // Checklist: centang/lepas centang, panel tetap terbuka (diterapkan saat ditutup).
            if (openMulti) {
                item.selected = !item.selected;
                if (li) {
                    if (item.selected) li.setAttribute('aria-selected', 'true');
                    else li.removeAttribute('aria-selected');
                }
                updateCount();
                input.focus({
                    preventScroll: true
                });
                return;
            }

            const select = openSelect;
            close(true, 'apply');
            if (valueDesc.get.call(select) !== item.value) {
                select.value = item.value;
                select.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            }
        }

        function position() {
            if (!openSelect) return;
            const r = openBtn.getBoundingClientRect();
            const vw = window.innerWidth;
            const vh = window.innerHeight;
            const width = Math.min(Math.max(r.width, 260), vw - 16);
            const left = Math.max(8, Math.min(r.left, vw - width - 8));
            const below = vh - r.bottom - 12;
            const above = r.top - 12;
            const flip = below < 220 && above > below;
            const room = Math.max(120, Math.min(flip ? above : below, 360));
            // Tinggi bagian di luar daftar: kolom cari (+ baris kaki di mode checklist).
            const chrome = openMulti ? 104 : 58;

            panel.style.width = width + 'px';
            panel.style.left = left + 'px';
            panel.style.maxHeight = room + 'px';
            list.style.maxHeight = Math.max(72, room - chrome) + 'px';
            if (flip) {
                panel.style.top = 'auto';
                panel.style.bottom = (vh - r.top + 4) + 'px';
            } else {
                panel.style.bottom = 'auto';
                panel.style.top = (r.bottom + 4) + 'px';
            }
        }

        function init() {
            document.querySelectorAll('select[data-searchable]').forEach(enhance);
        }

        // Refresh / tombol Back (bfcache): browser bisa memulihkan pilihan lama ke <select> asli -> samakan label tombolnya.
        window.addEventListener('pageshow', () => {
            document.querySelectorAll('select[data-searchable]').forEach((s) => refresh(s));
        });

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    })();
</script>
