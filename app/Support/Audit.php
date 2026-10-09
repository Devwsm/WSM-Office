<?php

namespace App\Support;

use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Audit
 * ---------------------------------------------------------------------
 * Pembantu menyusun teks `detail` Audit Log yang informatif: nilai lama →
 * baru untuk perubahan, dan ringkasan field untuk data baru/dihapus.
 *
 * `$labels` memetakan nama kolom ke label tampilan, atau ke [label, resolver]
 * bila nilainya perlu diterjemahkan (mis. id user → nama):
 *
 *   ['title' => 'Judul', 'pic_employee_id' => ['PIC', fn ($id) => User::find($id)?->name]]
 *
 * `changes()` HARUS dipanggil SEBELUM `$model->update()` (membandingkan nilai
 * yang ada dengan data baru). Isian yang tidak ada di `$labels` tidak
 * ditampilkan (jangan masukkan kolom sensitif seperti path file atau password).
 * ---------------------------------------------------------------------
 */
class Audit
{
    private const MAX_VALUE_LENGTH = 80;

    /** "Label: lama → baru; ..." untuk kolom berlabel yang benar-benar berubah. */
    public static function changes(Model $model, array $data, array $labels): string
    {
        $next = clone $model;
        $next->fill($data);
        $dirty = $next->getDirty();

        $parts = [];

        foreach ($labels as $column => $definition) {
            if (! array_key_exists($column, $dirty)) {
                continue;
            }

            [$label, $resolve] = self::unpack($definition);
            $old = self::value($model->getAttribute($column), $resolve);
            $new = self::value($next->getAttribute($column), $resolve);

            if ($old === $new) {
                continue; // beda tipe saja (mis. "100.00" vs 100), isinya sama
            }

            $parts[] = "{$label}: {$old} → {$new}";
        }

        return $parts === [] ? 'tanpa perubahan pada isian utama' : implode('; ', $parts);
    }

    /** "Label: nilai; ..." untuk data baru atau yang dihapus. */
    public static function summary(Model $model, array $labels): string
    {
        $parts = [];

        foreach ($labels as $column => $definition) {
            [$label, $resolve] = self::unpack($definition);
            $value = $model->getAttribute($column);

            if ($value === null || $value === '') {
                continue;
            }

            $parts[] = "{$label}: " . self::value($value, $resolve);
        }

        return implode('; ', $parts);
    }

    /** Nilai ringkas yang aman ditampilkan di log. */
    public static function value(mixed $value, ?callable $resolve = null): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if ($resolve !== null) {
            $resolved = $resolve($value);
            $value = ($resolved === null || $resolved === '') ? $value : $resolved;
        }

        if (is_bool($value)) {
            return $value ? 'ya' : 'tidak';
        }

        if ($value instanceof CarbonInterface) {
            return $value->format('d M Y');
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('d M Y');
        }

        if (is_array($value)) {
            $value = implode(', ', array_map(fn($v) => is_scalar($v) ? (string) $v : json_encode($v), $value));
        }

        $value = (string) $value;

        // "100.00" → "100", supaya angka desimal bawaan database tidak tampak berubah.
        if (is_numeric($value) && str_contains($value, '.')) {
            $value = rtrim(rtrim($value, '0'), '.');
        }

        return Str::limit(trim((string) preg_replace('/\s+/', ' ', $value)), self::MAX_VALUE_LENGTH);
    }

    /**
     * Label kolom per jenis data yang dicatat. Kolom sensitif atau panjang
     * (path file, isi memo/catatan panjang, password) sengaja tidak ada.
     *
     * @return array<string, string|array{0:string,1:callable}>
     */
    public static function labels(string $kind): array
    {
        $user = ['Karyawan', fn($id) => \App\Models\User::query()->find($id)?->name];
        $project = ['Project', fn($id) => \App\Models\Project::query()->find($id)?->name];

        return match ($kind) {
            'project' => [
                'name' => 'Nama',
                'status' => 'Status',
                'priority' => 'Prioritas',
                'start_date' => 'Mulai',
                'end_date' => 'Selesai',
                'lead_employee_id' => ['Lead', $user[1]],
                'visibility' => 'Visibility',
                'tracker_url' => 'Tracker URL',
                'progress_recap' => 'Progress Recap',
            ],
            'work_item' => [
                'project_id' => $project,
                'section' => 'Section',
                'title' => 'Judul',
                'due_date' => 'Due',
                'focus' => 'Focus',
                'pic_employee_id' => ['PIC', $user[1]],
                'progress' => 'Progress',
                'priority' => 'Prioritas',
                'notes' => 'Catatan',
                'link' => 'Link',
            ],
            'budget' => [
                'project_id' => $project,
                'category' => 'Kategori',
                'item' => 'Item',
                'song_title' => 'Lagu',
                'budget' => 'Budget',
                'actual' => 'Actual',
                'proof_link' => 'Bukti bayar',
                'note' => 'Catatan',
            ],
            'kpi' => [
                'employee_id' => $user,
                'title' => 'Judul',
                'period' => 'Periode',
                'target' => 'Target',
                'current' => 'Capaian',
                'unit' => 'Satuan',
                'weight' => 'Bobot',
                'due_date' => 'Due',
                'status' => 'Status',
                'owner_note' => 'Catatan Owner',
            ],
            'contract' => [
                'employee_id' => $user,
                'start_date' => 'Mulai',
                'end_date' => 'Berakhir',
                'notes' => 'Catatan',
                'original_filename' => 'File',
            ],
            'legal' => [
                'category' => 'Kategori',
                'title' => 'Judul',
                'party' => 'Pihak',
                'start_date' => 'Mulai',
                'end_date' => 'Berakhir',
                'notes' => 'Catatan',
                'original_filename' => 'File',
            ],
            'royalty' => [
                'title' => 'Judul',
                'period' => 'Periode',
                'source' => 'Sumber',
                'status' => 'Status',
                'gross' => 'Gross',
                'share_pct' => 'Share %',
                'recoup' => 'Recoup',
                'note' => 'Catatan',
            ],
            'meeting' => [
                'project_id' => $project,
                'date' => 'Tanggal',
                'time' => 'Jam',
                'agenda' => 'Agenda',
                'persons_text' => 'Peserta lain',
                'notes' => 'Catatan',
                'decisions' => 'Keputusan',
            ],
            'memo' => [
                'type' => 'Jenis',
                'title' => 'Judul',
                'meeting_date' => 'Tanggal',
                'pinned' => 'Pin',
                'audience' => 'Audiens',
                'active' => 'Aktif',
            ],
            'changelog' => [
                'version' => 'Versi',
                'release_date' => 'Rilis',
                'status' => 'Status',
                'title' => 'Judul',
            ],
            'job_opening' => [
                'title' => 'Judul',
                'division' => 'Divisi',
                'employment_type' => 'Tipe',
                'status' => 'Status',
            ],
            'job_application' => ['status' => 'Status', 'notes' => 'Catatan'],
            default => [],
        };
    }

    /** @return array{0:string,1:?callable} */
    private static function unpack(string|array $definition): array
    {
        return is_array($definition)
            ? [$definition[0], $definition[1] ?? null]
            : [$definition, null];
    }
}