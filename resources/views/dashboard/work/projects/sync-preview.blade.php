{{--
    dashboard/work/projects/sync-preview.blade.php
    ---------------------------------------------------------------------
    Preview hasil sinkron Sheet SEBELUM ada yang masuk database. Data dari
    ProjectSheetController::preview(): $project, $plan (rows + counts), $token
    (commit mengambil lagi hasil staging yang sama, bukan membaca ulang file),
    $fileName, $sheetName.
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Preview Sinkron — ' . $project->name, 'navActive' => 'modules'])

@section('content')
    @php
        $counts = $plan['counts'];
        $changed = collect($plan['rows'])
            ->whereIn('action', ['create', 'update'])
            ->values();
        $errorRows = collect($plan['rows'])->where('action', 'invalid')->values();
        $noteworthy = collect($plan['rows'])
            ->where('action', 'unchanged')
            ->filter(fn($r) => !empty($r['warnings']))
            ->values();
        $shownLimit = 300;
        $applicable = $counts['create'] + $counts['update'];
    @endphp

    <div class="mb-4">
        <a href="{{ route('dashboard.work.tracker.projects.sync.show', $project) }}"
            class="text-xs font-extrabold text-muted hover:text-ink">&larr; Upload Ulang</a>
        <h2 class="mt-1 text-[28px] font-black leading-[0.98] tracking-tight">Preview Sinkron — {{ $project->name }}</h2>
        <p class="mt-1 text-[13px] text-muted">File <strong>{{ $fileName }}</strong> · sheet
            <strong>{{ $sheetName }}</strong>. Cek dulu — belum ada yang tersimpan.
        </p>
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
        <span class="rounded-full bg-[#dff3ee] px-2.5 py-1 text-[10px] font-extrabold text-[#1f6b57]">{{ $counts['create'] }}
            task baru</span>
        <span class="rounded-full bg-[#dbe9fb] px-2.5 py-1 text-[10px] font-extrabold text-[#1f4f8f]">{{ $counts['update'] }}
            diperbarui</span>
        <span class="rounded-full bg-[#ece7dd] px-2.5 py-1 text-[10px] font-extrabold text-muted">{{ $counts['unchanged'] }}
            tidak berubah</span>
        <span
            class="rounded-full bg-[#fbe2df] px-2.5 py-1 text-[10px] font-extrabold text-[#8a2f24]">{{ $counts['invalid'] }}
            baris error</span>
        <span class="rounded-full bg-[#ece7dd] px-2.5 py-1 text-[10px] font-extrabold text-muted">{{ $counts['missing'] }}
            task di WSM tidak ada di file (dibiarkan)</span>
    </div>

    <div class="mb-2"><strong class="text-sm">Akan dibuat / diperbarui</strong></div>
    <div class="mb-6 overflow-x-auto rounded-wsm border border-line bg-white">
        <table class="w-full text-left text-xs">
            <thead>
                <tr class="border-b border-line bg-[#f4f1ea]">
                    <th class="whitespace-nowrap px-3.5 py-2.5 font-extrabold">Baris</th>
                    <th class="whitespace-nowrap px-3.5 py-2.5 font-extrabold">Aksi</th>
                    <th class="px-3.5 py-2.5 font-extrabold">Item</th>
                    <th class="px-3.5 py-2.5 font-extrabold">Section</th>
                    <th class="px-3.5 py-2.5 font-extrabold">Perubahan</th>
                    <th class="px-3.5 py-2.5 font-extrabold">Catatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($changed->take($shownLimit) as $row)
                    <tr class="border-b border-line align-top last:border-0">
                        <td class="whitespace-nowrap px-3.5 py-2.5 text-muted">{{ $row['row'] }}</td>
                        <td class="whitespace-nowrap px-3.5 py-2.5">
                            @if ($row['action'] === 'create')
                                <span class="badge-wsm-green">Baru</span>
                            @else
                                <span class="badge-wsm-blue">Update</span>
                            @endif
                        </td>
                        <td class="min-w-48 px-3.5 py-2.5 font-bold">{{ $row['title'] }}</td>
                        <td class="whitespace-nowrap px-3.5 py-2.5">
                            {{ $row['apply']['section'] ?? ($row['section'] ?: '-') }}</td>
                        <td class="min-w-56 px-3.5 py-2.5">
                            @foreach ($row['changes'] as $change)
                                <div>
                                    <span class="font-extrabold">{{ $change['field'] }}:</span>
                                    @if ($change['from'] !== '')
                                        <span class="text-muted line-through">{{ $change['from'] }}</span> →
                                    @endif
                                    {{ $change['to'] === '' ? '-' : $change['to'] }}
                                </div>
                            @endforeach
                        </td>
                        <td class="min-w-40 px-3.5 py-2.5 text-[#8a6100]">
                            @foreach ($row['warnings'] as $warning)
                                <div>{{ $warning }}</div>
                            @endforeach
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-3.5 py-6 text-center text-muted">Tidak ada perubahan — isi file sudah
                            sama
                            dengan WSM.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($changed->count() > $shownLimit)
        <p class="-mt-4 mb-6 text-xs text-muted">Menampilkan {{ $shownLimit }} dari {{ $changed->count() }} baris —
            semuanya
            tetap ikut diterapkan.</p>
    @endif

    @if ($noteworthy->isNotEmpty())
        <div class="mb-6 rounded-wsm border border-line bg-white p-4">
            <strong class="text-sm">Baris tidak berubah, tapi ada catatan</strong>
            <ul class="mt-2 space-y-1 text-xs text-[#8a6100]">
                @foreach ($noteworthy->take(20) as $row)
                    <li>Baris {{ $row['row'] }} ({{ $row['title'] }}): {{ implode(' ', $row['warnings']) }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($errorRows->isNotEmpty())
        <strong class="text-sm">Baris error — tidak ikut masuk</strong>
        <div class="mt-2 mb-6 overflow-x-auto rounded-wsm border border-line bg-white">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-line bg-[#f4f1ea]">
                        <th class="whitespace-nowrap px-3.5 py-2.5 font-extrabold">Baris</th>
                        <th class="px-3.5 py-2.5 font-extrabold">Item</th>
                        <th class="px-3.5 py-2.5 font-extrabold">Error</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($errorRows as $row)
                        <tr class="border-b border-line last:border-0">
                            <td class="whitespace-nowrap px-3.5 py-2.5 text-muted">{{ $row['row'] }}</td>
                            <td class="px-3.5 py-2.5">{{ $row['title'] ?: '-' }}</td>
                            <td class="px-3.5 py-2.5 text-[#8a2f24]">
                                @foreach ($row['errors'] as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <form method="POST" action="{{ route('dashboard.work.tracker.projects.sync.commit', $project) }}" class="mt-2"
        data-confirm="Terapkan {{ $counts['create'] }} task baru dan {{ $counts['update'] }} pembaruan ke project &quot;{{ $project->name }}&quot;?"
        data-confirm-title="Terapkan sinkron?" data-confirm-button="Ya, terapkan">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        @if ($applicable > 0)
            <button type="submit" class="btn-wsm-black inline-flex items-center justify-center">Terapkan Sinkron
                ({{ $applicable }} baris)</button>
        @endif
        <a href="{{ route('dashboard.work.tracker.projects.sync.show', $project) }}"
            class="ml-2 inline-block rounded-2xl border border-line px-4 py-2.5 text-xs font-extrabold text-[#5e5951] hover:bg-[#f2f0eb]">Batal</a>
    </form>
@endsection
