<?php

namespace App\Support\ProjectSheet;

use App\Models\Project;
use App\Models\ProjectSection;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * SheetSync — sinkron task satu project dengan isi tracker Google Sheet / Excel.
 * Padanan `v27SyncSourceObject()` (import Google Sheet sebagai linked source) di
 * prototype v32, dengan tiga perbedaan yang disengaja:
 *
 *   1. Dua tahap. plan() cuma MENGHITUNG apa yang akan berubah (dipakai halaman
 *      preview), apply() baru menulis ke database setelah dikonfirmasi.
 *   2. Task dicocokkan lewat kolom "ID WSM" (stabil), bukan nomor baris sheet
 *      (bergeser kalau ada yang menyisipkan baris). Baris tanpa ID dicocokkan
 *      lewat section + judul, jadi tracker Google Sheet mentah pun bisa
 *      disinkron pertama kali TANPA menggandakan task yang sudah ada.
 *   3. Tidak pernah menghapus. Task di WSM yang tidak ada di file dibiarkan, dan
 *      sel kosong di file TIDAK mengosongkan data di WSM — file mentah dari
 *      Google Sheet banyak sel kosong (tanggal, catatan) dan tidak boleh
 *      menimpa apa yang sudah diisi tim di WSM.
 */
final class SheetSync
{
    private const MAX_TITLE = 255;

    private const MAX_SECTION = 80;

    private const MAX_LINK = 255;

    /** Kata pengantar nama yang dibuang sebelum dicocokkan ke karyawan. */
    private const HONORIFICS = '/^(mas|mba|mbak|kak|om|pak|bu|bang)\s+/iu';

    private const PROGRESS_ALIASES = [
        'done' => 'Done',
        'selesai' => 'Done',
        'pending' => 'Pending',
        'on development' => 'On Development',
        'ondevelopment' => 'On Development',
        'on going' => 'On Development',
        'ongoing' => 'On Development',
        'in progress' => 'On Development',
        'development' => 'On Development',
        'follow up' => 'Follow Up',
        'followup' => 'Follow Up',
        'fu' => 'Follow Up',
        'confirmed' => 'Confirmed',
        'confirm' => 'Confirmed',
        'postpone' => 'Postpone',
        'postponed' => 'Postpone',
        'hold' => 'Postpone',
    ];

    /** @var Collection<int, User> */
    private Collection $users;

    public function __construct(private readonly Project $project)
    {
        $this->users = User::query()->orderBy('id')->get(['id', 'name', 'email']);
    }

    /**
     * @param  list<array<string, mixed>>  $sheetRows  keluaran SheetReader::parse()['rows']
     * @return array{rows: list<array<string, mixed>>, counts: array<string, int>}
     */
    public function plan(array $sheetRows): array
    {
        $items = WorkItem::query()
            ->with(['pic:id,name', 'additionalPics:id,name'])
            ->where('project_id', $this->project->id)
            ->orderBy('id')
            ->get()
            ->keyBy('id');

        $sections = ProjectSection::query()->where('project_id', $this->project->id)->pluck('name')
            ->merge($items->pluck('section'))
            ->map(fn($s) => (string) $s)->filter()->unique()
            ->mapWithKeys(fn($s) => [$this->norm($s) => $s]);

        // Task yang diklaim baris ber-ID tidak boleh lagi ikut antrean pencocokan judul.
        $claimed = [];
        foreach ($sheetRows as $row) {
            if ($row['id'] !== '' && $items->has((int) $row['id'])) {
                $claimed[(int) $row['id']] = true;
            }
        }

        $queues = [];
        foreach ($items as $item) {
            if (! isset($claimed[$item->id])) {
                $queues[$this->norm((string) $item->section) . '|' . $this->norm($item->title)][] = $item->id;
            }
        }

        $matched = [];
        $out = [];
        $counts = ['create' => 0, 'update' => 0, 'unchanged' => 0, 'invalid' => 0, 'missing' => 0];

        foreach ($sheetRows as $row) {
            $result = $this->planRow($row, $items, $queues, $matched, $sections);
            $counts[$result['action']]++;
            $out[] = $result;
        }

        $counts['missing'] = $items->count() - count($matched);

        return ['rows' => $out, 'counts' => $counts];
    }

    /**
     * Tulis hasil plan() ke database. Baris update yang task-nya sudah terhapus
     * sejak preview dilewati (dihitung di 'skipped'), bukan bikin error.
     *
     * @param  array{rows: list<array<string, mixed>>}  $plan
     * @return array{created: int, updated: int, skipped: int}
     */
    public function apply(array $plan, User $actor): array
    {
        $created = $updated = $skipped = 0;

        DB::transaction(function () use ($plan, $actor, &$created, &$updated, &$skipped) {
            foreach ($plan['rows'] as $row) {
                if ($row['action'] === 'create') {
                    $data = $row['apply'];
                    $pics = $row['pics'] ?? null;

                    if (($data['item_no'] ?? '') === '') {
                        $data['item_no'] = $this->nextItemNo($data['section']);
                    }

                    $item = WorkItem::create($data + [
                        'project_id' => $this->project->id,
                        'created_by' => $actor->id,
                        'is_reminder' => false,
                    ]);

                    $this->writePics($item, $pics);
                    $created++;
                } elseif ($row['action'] === 'update') {
                    $item = WorkItem::query()->where('project_id', $this->project->id)->find($row['id']);

                    if ($item === null) {
                        $skipped++;

                        continue;
                    }

                    if ($row['apply'] !== []) {
                        $item->update($row['apply']);
                    }

                    $this->writePics($item, $row['pics'] ?? null);
                    $updated++;
                }
            }
        });

        return ['created' => $created, 'updated' => $updated, 'skipped' => $skipped];
    }

    // ---------------------------------------------------------------------

    /**
     * @param  Collection<int, WorkItem>  $items
     * @param  array<string, list<int>>  $queues
     * @param  array<int, true>  $matched
     * @param  Collection<string, string>  $sections
     * @return array<string, mixed>
     */
    private function planRow(array $row, Collection $items, array &$queues, array &$matched, Collection $sections): array
    {
        $base = [
            'row' => $row['row'],
            'action' => 'invalid',
            'id' => null,
            'title' => $row['title'],
            'section' => $row['section'],
            'changes' => [],
            'apply' => [],
            'pics' => null,
            'warnings' => [],
            'errors' => [],
        ];

        // --- validasi baris ---
        $errors = [];
        $item = null;

        if ($row['id'] !== '') {
            $item = ctype_digit($row['id']) ? $items->get((int) $row['id']) : null;

            if ($item === null) {
                $errors[] = "ID WSM \"{$row['id']}\" bukan task di project ini.";
            } elseif (isset($matched[$item->id])) {
                $errors[] = "ID WSM {$row['id']} muncul lebih dari sekali di file.";
            }
        }

        if ($row['title'] === '') {
            $errors[] = 'Kolom ITEM kosong.';
        } elseif (mb_strlen($row['title']) > self::MAX_TITLE) {
            $errors[] = 'ITEM terlalu panjang (maksimal ' . self::MAX_TITLE . ' karakter).';
        }

        $section = $this->canonicalSection($row['section'], $sections);

        if (mb_strlen($section) > self::MAX_SECTION) {
            $errors[] = 'SECTION terlalu panjang (maksimal ' . self::MAX_SECTION . ' karakter).';
        }

        if ($errors !== []) {
            return ['errors' => $errors] + $base;
        }

        // --- cari task yang cocok kalau baris tidak ber-ID ---
        if ($item === null) {
            $key = $this->norm($section) . '|' . $this->norm($row['title']);

            if (! empty($queues[$key])) {
                $item = $items->get(array_shift($queues[$key]));
            } elseif ($section === '') {
                // File tanpa kolom SECTION: cocokkan lewat judul saja (di section mana pun).
                $suffix = '|' . $this->norm($row['title']);
                foreach ($queues as $queueKey => $ids) {
                    if ($ids !== [] && str_ends_with($queueKey, $suffix)) {
                        $item = $items->get(array_shift($queues[$queueKey]));
                        break;
                    }
                }
            }
        }

        // --- nilai dari file ---
        $warnings = [];
        $due = SheetReader::parseDate($row['date']);
        if ($due === false) {
            $warnings[] = 'DATE "' . $this->shorten((string) (is_scalar($row['date']) ? $row['date'] : '')) . '" tidak terbaca sebagai tanggal — dilewati.';
            $due = null;
        }

        $progress = null;
        if ($row['progress'] !== '') {
            $progress = $this->mapProgress($row['progress']);
            if ($progress === null) {
                $warnings[] = 'PROGRESS "' . $this->shorten($row['progress']) . '" tidak dikenali — dilewati.';
            }
        }

        $link = null;
        if ($row['link'] !== '') {
            $link = $this->normalizeLink($row['link']);
            if ($link !== null && mb_strlen($link) > self::MAX_LINK) {
                $warnings[] = 'LINK terlalu panjang (maksimal ' . self::MAX_LINK . ' karakter) — dilewati.';
                $link = null;
            }
        }

        $picCells = array_values(array_filter([$row['pic'], $row['pic2'], $row['pic3']], fn($c) => $c !== ''));
        $picPlan = $picCells === [] ? null : $this->resolvePics($picCells, $warnings);

        // --- task baru ---
        if ($item === null) {
            $apply = [
                'section' => $section !== '' ? $section : 'OTHER',
                'item_no' => $row['no'],
                'title' => $row['title'],
                'due_date' => $due,
                'progress' => $progress ?? 'Pending',
                'notes' => $row['note'] !== '' ? $row['note'] : null,
                'link' => $link,
            ];

            return [
                'action' => 'create',
                'apply' => $apply,
                'pics' => $picPlan,
                'warnings' => $warnings,
                'changes' => $this->describeNew($apply, $picPlan),
            ] + $base;
        }

        $matched[$item->id] = true;

        // --- task ada: hitung selisih ---
        $apply = [];
        $changes = [];

        $set = function (string $column, string $label, mixed $old, mixed $new, ?string $shownOld = null, ?string $shownNew = null) use (&$apply, &$changes) {
            $apply[$column] = $new;
            $changes[] = [
                'field' => $label,
                'from' => $shownOld ?? (string) ($old ?? ''),
                'to' => $shownNew ?? (string) ($new ?? ''),
            ];
        };

        if ($section !== '' && $section !== (string) $item->section) {
            $set('section', 'Section', $item->section, $section);
        }
        if ($row['no'] !== '' && $row['no'] !== (string) $item->item_no) {
            $set('item_no', 'No', $item->item_no, $row['no']);
        }
        if ($row['title'] !== $item->title) {
            $set('title', 'Item', $item->title, $row['title']);
        }
        if ($due !== null && $due !== optional($item->due_date)->format('Y-m-d')) {
            $set('due_date', 'Date', $item->due_date, $due, optional($item->due_date)->format('d/m/Y') ?? '', $this->dmy($due));
        }
        if ($progress !== null && $progress !== $item->progress) {
            $set('progress', 'Progress', $item->progress, $progress);
        }
        if ($row['note'] !== '' && $this->eol($row['note']) !== $this->eol((string) $item->notes)) {
            $set('notes', 'Note', $item->notes, $row['note'], $this->shorten((string) $item->notes, 60), $this->shorten($row['note'], 60));
        }
        if ($link !== null && $link !== (string) $item->link) {
            $set('link', 'Link', $item->link, $link);
        }

        $picsToWrite = null;
        if ($picPlan !== null && $this->picsDiffer($item, $picPlan)) {
            $picsToWrite = $picPlan;
            $changes[] = [
                'field' => 'PIC',
                'from' => $item->picLabel('-'),
                'to' => $this->describePics($picPlan),
            ];
        }

        return [
            'action' => $changes === [] ? 'unchanged' : 'update',
            'id' => $item->id,
            'apply' => $apply,
            'pics' => $picsToWrite,
            'changes' => $changes,
            'warnings' => $warnings,
        ] + $base;
    }

    /**
     * @param  list<string>  $cells
     * @param  list<string>  $warnings
     * @return array{ids: list<int>, names: list<string>, text: ?string}
     */
    private function resolvePics(array $cells, array &$warnings): array
    {
        $ids = [];
        $unresolved = [];

        foreach ($cells as $cell) {
            foreach (preg_split('/\s*(?:·|•|&|,|;|\/|\+|\n|\bdan\b)\s*/iu', $cell) ?: [] as $token) {
                $token = trim($token);

                if ($token === '') {
                    continue;
                }

                $user = $this->findUser($token);

                if ($user !== null) {
                    $ids[$user->id] = $user->name;
                } else {
                    $unresolved[] = $token;
                }
            }
        }

        $ids = array_slice($ids, 0, 1 + WorkItem::MAX_ADDITIONAL_PICS, true);

        // Teks bebas hanya dipakai kalau task tidak punya PIC tambahan nyata
        // (aturan yang sama dengan WorkTrackerBoardController::syncAdditionalPics).
        $text = null;
        if ($unresolved !== []) {
            if (count($ids) > 1) {
                $warnings[] = 'PIC "' . implode(', ', array_map(fn($t) => $this->shorten($t, 30), $unresolved)) . '" tidak dikenali — tidak disimpan.';
            } else {
                $text = mb_substr(implode(' · ', array_unique($unresolved)), 0, 255);
                if (mb_strtoupper($text) !== 'ALL TEAM') {
                    $warnings[] = 'PIC "' . $this->shorten($text, 40) . '" bukan karyawan terdaftar — disimpan sebagai teks saja.';
                }
            }
        }

        return ['ids' => array_keys($ids), 'names' => array_values($ids), 'text' => $text];
    }

    private function findUser(string $token): ?User
    {
        $needle = $this->norm(preg_replace(self::HONORIFICS, '', preg_replace('/\s+(ws\s+)?team$/iu', '', $token) ?? $token) ?? $token);

        if ($needle === '' || $needle === 'all') {
            return null;
        }

        $byName = $this->users->first(fn(User $u) => $this->norm($u->name) === $needle)
            ?? $this->users->first(fn(User $u) => $this->norm($u->email) === $this->norm($token));

        if ($byName !== null) {
            return $byName;
        }

        $byFirst = $this->users->filter(fn(User $u) => explode(' ', $this->norm($u->name))[0] === $needle);

        return $byFirst->count() === 1 ? $byFirst->first() : null;
    }

    /** @param  array{ids: list<int>, names: list<string>, text: ?string}  $plan */
    private function picsDiffer(WorkItem $item, array $plan): bool
    {
        $current = $item->allPics()->pluck('id')->map(fn($v) => (int) $v)->all();

        return $current !== $plan['ids'] || (string) $item->additional_pic !== (string) ($plan['text'] ?? '');
    }

    /** @param  array{ids: list<int>, names: list<string>, text: ?string}|null  $plan */
    private function writePics(WorkItem $item, ?array $plan): void
    {
        if ($plan === null) {
            return;
        }

        $ids = $plan['ids'];
        $item->update([
            'pic_employee_id' => $ids[0] ?? null,
            'additional_pic' => $plan['text'],
        ]);
        $item->additionalPics()->sync(array_slice($ids, 1));
    }

    /** @param  array{ids: list<int>, names: list<string>, text: ?string}  $plan */
    private function describePics(array $plan): string
    {
        $parts = $plan['names'];

        if ($plan['text'] !== null) {
            $parts[] = $plan['text'];
        }

        return $parts === [] ? '-' : implode(' · ', $parts);
    }

    /** @return list<array{field: string, from: string, to: string}> */
    private function describeNew(array $apply, ?array $picPlan): array
    {
        $rows = [
            ['field' => 'Section', 'from' => '', 'to' => (string) $apply['section']],
            ['field' => 'Progress', 'from' => '', 'to' => (string) $apply['progress']],
        ];

        if ($apply['due_date'] !== null) {
            $rows[] = ['field' => 'Date', 'from' => '', 'to' => $this->dmy($apply['due_date'])];
        }

        if ($picPlan !== null) {
            $rows[] = ['field' => 'PIC', 'from' => '', 'to' => $this->describePics($picPlan)];
        }

        return $rows;
    }

    private function nextItemNo(string $section): string
    {
        // item_no berupa string di database, jadi max() SQL membandingkan urutan
        // huruf ("9" > "10") — hitung numeriknya di PHP.
        $max = WorkItem::query()
            ->where('project_id', $this->project->id)
            ->where('section', $section)
            ->pluck('item_no')
            ->filter(fn($n) => is_numeric($n))
            ->map(fn($n) => (int) $n)
            ->max();

        return (string) (($max ?? 0) + 1);
    }

    private function canonicalSection(string $section, Collection $known): string
    {
        $section = trim($section);

        if ($section === '') {
            return '';
        }

        return $known->get($this->norm($section)) ?? mb_strtoupper($section);
    }

    private function mapProgress(string $value): ?string
    {
        $key = $this->norm($value);

        foreach (WorkItem::PROGRESS_OPTIONS as $option) {
            if ($this->norm($option) === $key) {
                return $option;
            }
        }

        return self::PROGRESS_ALIASES[$key] ?? null;
    }

    /** Hanya URL sungguhan yang disimpan; teks seperti "LINK" atau "Budget & Rundown" diabaikan. */
    private function normalizeLink(string $value): ?string
    {
        $value = trim(explode("\n", $value)[0]);

        if (preg_match('#^https?://\S+$#i', $value)) {
            return $value;
        }

        if (preg_match('#^(?:[a-z0-9-]+\.)+[a-z]{2,}(?:/\S*)?$#i', $value)) {
            return 'https://' . $value;
        }

        return null;
    }

    private function norm(?string $value): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', (string) $value)));
    }

    private function dmy(string $ymd): string
    {
        return \DateTimeImmutable::createFromFormat('!Y-m-d', $ymd)->format('d/m/Y');
    }

    private function eol(string $value): string
    {
        return trim(str_replace(["\r\n", "\r"], "\n", $value));
    }

    private function shorten(string $value, int $max = 40): string
    {
        return mb_strlen($value) > $max ? mb_substr($value, 0, $max - 1) . '…' : $value;
    }
}