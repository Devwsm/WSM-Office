@php
    $budget ??= null;
    $prefill ??= ['project_id' => null, 'category' => ''];
    // Saran isian (datalist) — dikirim controller; di-include otomatis dari create/edit.
    $categorySuggestions ??= [];
    $songSuggestions ??= [];
    $categoriesByProject ??= [];
    $selectedProject = old('project_id', $budget->project_id ?? ($prefill['project_id'] ?? ''));
    $selectedCategory = old('category', $budget->category ?? ($prefill['category'] ?? ''));
@endphp

<div class="grid gap-4">
    <div class="grid grid-cols-1 items-start gap-4 sm:grid-cols-2">
        <div>
            <label class="field-label-wsm mb-1.5" for="budgetProject">Project</label>
            <select name="project_id" id="budgetProject" class="input-wsm" required onchange="budgetRebuildCategories('')">
                <option value="" disabled @selected($selectedProject === '' || $selectedProject === null)>Pilih project…</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}" @selected((string) $selectedProject === (string) $project->id)>
                        {{ $project->name }}
                    </option>
                @endforeach
            </select>
            @error('project_id')
                <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="field-label-wsm mb-1.5" for="budgetCategorySelect">Kategori</label>
            {{-- Pilih kategori yang sudah ada di project, atau buat baru. Nilai yang dikirim tetap `category`. --}}
            <select id="budgetCategorySelect" class="input-wsm" onchange="budgetCategoryChanged()"></select>
            <input type="text" name="category" id="budgetCategory" value="{{ $selectedCategory }}"
                placeholder="Nama kategori baru (mis. Marketing)" class="input-wsm mt-2 hidden"
                list="budget-category-options" autocomplete="off" maxlength="100">
            <p id="budgetCategoryHint" class="mt-1 hidden text-[11px] font-bold text-[#8a5a00]"></p>
            <datalist id="budget-category-options">
                @foreach ($categorySuggestions as $suggestion)
                    <option value="{{ $suggestion }}"></option>
                @endforeach
            </datalist>
            @error('category')
                <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label class="field-label-wsm mb-1.5">Item</label>
        <input type="text" name="item" value="{{ old('item', $budget->item ?? '') }}" class="input-wsm" required>
        @error('item')
            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="field-label-wsm mb-1.5">Budget (Rp)</label>
            <input type="number" step="1000" name="budget" value="{{ old('budget', $budget->budget ?? '') }}"
                class="input-wsm" min="0" required>
            @error('budget')
                <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="field-label-wsm mb-1.5">Actual (Rp)</label>
            <input type="number" step="1000" name="actual" value="{{ old('actual', $budget->actual ?? 0) }}"
                class="input-wsm" min="0">
            @error('actual')
                <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="field-label-wsm mb-1.5">Lagu (opsional)</label>
            <input type="text" name="song_title" value="{{ old('song_title', $budget->song_title ?? '') }}"
                placeholder="Kosongkan kalau tidak terkait lagu" class="input-wsm" list="budget-song-options"
                autocomplete="off" maxlength="150">
            <datalist id="budget-song-options">
                @foreach ($songSuggestions as $suggestion)
                    <option value="{{ $suggestion }}"></option>
                @endforeach
            </datalist>
            @error('song_title')
                <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="field-label-wsm mb-1.5">Link Bukti Bayar (opsional)</label>
            <input type="text" inputmode="url" name="proof_link"
                value="{{ old('proof_link', $budget->proof_link ?? '') }}"
                placeholder="Link Google Drive bukti pembayaran" class="input-wsm" maxlength="500">
            @error('proof_link')
                <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label class="field-label-wsm mb-1.5">Catatan (opsional)</label>
        <textarea name="note" rows="3" class="input-wsm">{{ old('note', $budget->note ?? '') }}</textarea>
        @error('note')
            <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
        @enderror
    </div>
</div>

<script>
    // Kategori per project: HANYA yang sudah ada (dan boleh dilihat), atau buat baru.
    // Project belum punya kategori -> langsung minta nama kategori pertamanya.
    const budgetCategories = @json($categoriesByProject);

    function budgetRebuildCategories(selected) {
        const pid = document.getElementById('budgetProject').value;
        const sel = document.getElementById('budgetCategorySelect');
        const input = document.getElementById('budgetCategory');
        const hint = document.getElementById('budgetCategoryHint');
        const list = pid ? (budgetCategories[pid] || []) : [];
        const opt = (value, label, disabled = false) => {
            const o = document.createElement('option');
            o.value = value;
            o.textContent = label;
            o.disabled = disabled;
            return o;
        };
        const useInput = (on, value = '') => {
            input.classList.toggle('hidden', !on);
            input.required = on;
            input.disabled = !on;
            input.value = on ? value : '';
        };

        sel.innerHTML = '';
        hint.classList.add('hidden');
        sel.classList.remove('hidden');

        if (!pid) {
            sel.appendChild(opt('', 'Pilih project dulu', true));
            sel.value = '';
            sel.disabled = true;
            useInput(false);
            return;
        }

        if (list.length === 0) {
            sel.classList.add('hidden');
            sel.disabled = true;
            hint.textContent = 'Project ini belum punya kategori. Buat kategori pertamanya.';
            hint.classList.remove('hidden');
            useInput(true, selected || '');
            return;
        }

        sel.disabled = false;
        sel.appendChild(opt('', 'Pilih kategori…', true));
        list.forEach((name) => sel.appendChild(opt(name, name)));
        sel.appendChild(opt('__new__', '+ Kategori baru…'));

        const match = list.find((n) => n.toLowerCase() === (selected || '').trim().toLowerCase());
        if (match) {
            sel.value = match;
            // Dikirim lewat input tersembunyi-nilai: aktifkan input hanya untuk kategori baru.
            input.disabled = false;
            input.classList.add('hidden');
            input.required = false;
            input.value = match;
        } else if (selected) {
            sel.value = '__new__';
            useInput(true, selected);
        } else {
            sel.value = '';
            input.disabled = false;
            input.classList.add('hidden');
            input.required = false;
            input.value = '';
        }
    }

    function budgetCategoryChanged() {
        const sel = document.getElementById('budgetCategorySelect');
        const input = document.getElementById('budgetCategory');
        input.disabled = false;
        if (sel.value === '__new__') {
            input.classList.remove('hidden');
            input.required = true;
            input.value = '';
            input.focus();
        } else {
            input.classList.add('hidden');
            input.required = false;
            input.value = sel.value;
        }
    }

    budgetRebuildCategories(@json((string) $selectedCategory));

    // Kategori wajib dipilih/diisi sebelum submit (select-nya tidak punya name sendiri).
    document.getElementById('budgetCategory').form.addEventListener('submit', (e) => {
        const input = document.getElementById('budgetCategory');
        if (!input.value.trim()) {
            e.preventDefault();
            const msg = 'Pilih kategori atau buat kategori baru dulu.';
            if (window.WsmAlert) WsmAlert.error(msg);
            else alert(msg);
        }
    });
</script>
