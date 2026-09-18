{{--
    dashboard/export-import/_filters-form.blade.php
    -----------------------------------------------------------------
    Batch 2 — diekstrak dari preview.blade.php pas picker.blade.php
    (payroll/meetings PDF) butuh form filter yang SAMA PERSIS (month/
    date/select). Include lewat @include(...) dengan variabel
    `$filters` di scope pemanggil — jangan duplikat markup ini lagi
    kalau ada view export/import baru, tinggal include partial ini.
    -----------------------------------------------------------------
--}}
@if (!empty($filters))
    <form method="GET" class="mb-4 flex flex-wrap items-end gap-2 rounded-wsm border border-line bg-white p-3.5">
        @foreach ($filters as $filter)
            <div>
                <label class="mb-1 block text-[11px] font-extrabold text-muted">{{ $filter['label'] }}</label>
                @if ($filter['type'] === 'month')
                    <input type="month" name="{{ $filter['name'] }}" value="{{ $filter['value'] }}"
                        class="rounded-xl border border-line px-3 py-2 text-sm">
                @elseif ($filter['type'] === 'date')
                    <input type="date" name="{{ $filter['name'] }}" value="{{ $filter['value'] }}"
                        class="rounded-xl border border-line px-3 py-2 text-sm">
                @elseif ($filter['type'] === 'select')
                    <select name="{{ $filter['name'] }}" class="rounded-xl border border-line px-3 py-2 text-sm">
                        <option value="">Semua</option>
                        @foreach ($filter['options'] as $optValue => $optLabel)
                            <option value="{{ $optValue }}" @selected((string) $filter['value'] === (string) $optValue)>
                                {{ $optLabel }}
                            </option>
                        @endforeach
                    </select>
                @endif
            </div>
        @endforeach
        <button type="submit"
            class="rounded-2xl bg-[#f2f0eb] px-4 py-2 text-xs font-extrabold text-ink hover:bg-[#e8e5dc]">
            Terapkan Filter
        </button>
    </form>
@endif
