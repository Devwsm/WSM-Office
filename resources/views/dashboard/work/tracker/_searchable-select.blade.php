{{--
    Dropdown dengan pencarian realtime (2026-10-14)
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
    Atribut opsional: data-search-placeholder="Cari ...".
    ---------------------------------------------------------------------
--}}
<style>
    .wt-ss {
        position: relative;
        display: block;
        min-width: 0;
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
        text-align: left;
        cursor: pointer;
        max-width: 100%;
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

    .wt-ss-btn:disabled {
        cursor: not-allowed;
        opacity: .6;
    }

    #wtSsPanel {
        position: fixed;
        z-index: 90;
        display: none;
        flex-direction: column;
        gap: .35rem;
        padding: .45rem;
        border: 1px solid var(--color-line, #e4dfd5);
        border-radius: 1rem;
        background: #fff;
        box-shadow: 0 14px 38px rgba(23, 19, 10, .18);
        font-size: 12px;
    }

    #wtSsPanel.is-open {
        display: flex;
    }

    #wtSsInput {
        width: 100%;
        border: 1px solid var(--color-line, #e4dfd5);
        border-radius: .75rem;
        background: #faf8f4;
        padding: .45rem .7rem;
        font-size: 12px;
        font-weight: 700;
        outline: none;
    }

    #wtSsInput:focus {
        border-color: #17130a;
        background: #fff;
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
        border-radius: .65rem;
        font-weight: 700;
        cursor: pointer;
    }

    #wtSsList li[data-active="1"] {
        background: #f1ead9;
    }

    #wtSsList li[aria-selected="true"]::after {
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

    @media (max-width: 640px) {

        /* 16px mencegah iOS zoom otomatis saat kolom cari difokuskan. */
        #wtSsInput {
            font-size: 16px;
        }

        #wtSsList li {
            padding: .6rem .7rem;
            font-size: 13px;
        }
    }
</style>

<script>
    (function() {
        const valueDesc = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'value');
        const norm = (t) => String(t ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();

        let panel, input, list;
        let openSelect = null; // <select> yang sedang dibuka
        let openBtn = null;
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

            panel.append(input, list);
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
                if (li) pick(shown[Number(li.dataset.idx)]);
            });

            // Klik di luar panel & tombol aktif -> tutup.
            document.addEventListener('pointerdown', (e) => {
                if (!openSelect) return;
                if (panel.contains(e.target) || (openBtn && openBtn.contains(e.target))) return;
                close(false);
            }, true);

            // Scroll/resize: ikuti posisi tombol (keyboard HP bisa memicu resize/scroll), tutup bila tombol hilang dari layar.
            const follow = () => {
                if (!openSelect) return;
                const r = openBtn.getBoundingClientRect();
                if (r.bottom < 0 || r.top > window.innerHeight || (window.innerWidth !== openWidth)) {
                    close(false);
                    return;
                }
                position();
            };
            window.addEventListener('scroll', (e) => {
                if (openSelect && !panel.contains(e.target)) follow();
            }, true);
            window.addEventListener('resize', follow);
        }

        function labelOf(select) {
            const opt = select.selectedOptions && select.selectedOptions[0];
            return opt ? opt.textContent.trim() : '';
        }

        function refresh(select) {
            const btn = select._wtSsBtn;
            if (!btn) return;
            btn.querySelector('.wt-ss-label').textContent = labelOf(select);
            btn.dataset.empty = valueDesc.get.call(select) === '' ? '1' : '0';
            btn.disabled = select.disabled;
        }

        function enhance(select) {
            if (select._wtSsBtn || select.multiple) return;

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
            // setter ini menjaga label tombol selalu sama dengan nilai select.
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
            select.addEventListener('change', () => refresh(select));
            if (select.form) select.form.addEventListener('reset', () => setTimeout(() => refresh(select), 0));
            // Validasi `required` memfokuskan select asli -> teruskan ke tombol yang terlihat.
            select.addEventListener('focus', () => btn.focus());

            btn.addEventListener('click', () => {
                if (openSelect === select) {
                    close(true);
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
            if (openSelect) close(false);
            if (select.disabled) return;

            openSelect = select;
            openBtn = btn;
            openWidth = window.innerWidth;
            items = [...select.options]
                .filter((o) => !(o.value === '' && o.disabled)) // placeholder "Pilih ..." bukan pilihan
                .map((o) => ({
                    value: o.value,
                    text: o.textContent.trim(),
                    disabled: o.disabled,
                    selected: o.value === valueDesc.get.call(select),
                    key: norm(o.textContent),
                }));

            input.placeholder = select.dataset.searchPlaceholder || 'Cari…';
            input.value = '';
            btn.setAttribute('aria-expanded', 'true');
            panel.classList.add('is-open');
            render('');
            position();
            input.focus({
                preventScroll: true
            });
        }

        function close(refocus) {
            if (!openSelect) return;
            openBtn.setAttribute('aria-expanded', 'false');
            panel.classList.remove('is-open');
            const btn = openBtn;
            openSelect = null;
            openBtn = null;
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
                if (active >= 0) pick(shown[active]);
            } else if (e.key === 'Escape') {
                e.preventDefault();
                close(true);
            } else if (e.key === 'Tab') {
                close(false);
            }
        }

        function pick(item) {
            if (!item || item.disabled || !openSelect) return;
            const select = openSelect;
            close(true);
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
            const width = Math.min(Math.max(r.width, 240), vw - 16);
            const left = Math.max(8, Math.min(r.left, vw - width - 8));
            const below = vh - r.bottom - 12;
            const above = r.top - 12;
            const flip = below < 220 && above > below;
            const room = Math.max(120, Math.min(flip ? above : below, 340));

            panel.style.width = width + 'px';
            panel.style.left = left + 'px';
            panel.style.maxHeight = room + 'px';
            list.style.maxHeight = (room - 58) + 'px';
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

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    })();
</script>
