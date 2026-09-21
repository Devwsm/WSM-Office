<?php

namespace App\Imports;

use App\Models\Kpi;
use App\Models\User;
use Illuminate\Validation\Rule;

/**
 * KpiImport — Batch 4 (lanjutan, KPI).
 * Dipakai controller: App\Http\Controllers\Dashboard\ExportImport\ImportController
 * Catalog key: 'kpi' (lihat App\Support\ExportImport\ExportCatalog)
 *
 * Pola sama seperti WorkItemImport (Batch 3) buat kolom 'karyawan' —
 * teks bebas nama/email di Excel, dicocokkan ke User yang sudah ada
 * (BUKAN bikin akun baru kayak EmployeeImport — kalau namanya gak
 * ketemu, baris ditolak, gak dianggap kosong/opsional, beda dari 'pic'
 * di WorkItemImport yang boleh kosong).
 *
 * KpiRequest (form manual Owner/Manajer) mewajibkan 'current' &
 * 'status' — di sini keduanya SENGAJA dibikin nullable dengan fallback
 * (current -> 0, status -> 'Active') biar bulk-import KPI baru yang
 * belum ada progress-nya tetap bisa lolos per baris tanpa harus isi
 * manual, sama alasan 'progress' nullable di WorkItemImport.
 */
class KpiImport extends BaseImport
{
    public function templateHeadings(): array
    {
        return ['karyawan', 'judul_kpi', 'periode', 'target', 'capaian', 'satuan', 'bobot', 'tenggat', 'status', 'catatan_owner'];
    }

    public function previewColumns(): array
    {
        return [
            ['label' => 'Karyawan', 'key' => 'employee_name'],
            ['label' => 'Judul KPI', 'key' => 'title'],
            ['label' => 'Periode', 'key' => 'period'],
            ['label' => 'Target', 'key' => 'target'],
            ['label' => 'Capaian', 'key' => 'current'],
            ['label' => 'Satuan', 'key' => 'unit', 'fallback' => '-'],
            ['label' => 'Bobot (%)', 'key' => 'weight', 'fallback' => '-'],
            ['label' => 'Tenggat', 'key' => 'due_date', 'type' => 'date', 'fallback' => '-'],
            ['label' => 'Status', 'key' => 'status'],
        ];
    }

    public function fieldNotes(): array
    {
        return [
            'karyawan' => ['required' => true, 'note' => 'Isi nama atau email karyawan yang SUDAH terdaftar (import ini gak bikin akun baru — kalau mau sekalian bikin akun baru, pakai Import Manajemen Karyawan dulu).'],
            'judul_kpi' => ['required' => true],
            'periode' => ['required' => true, 'note' => 'Teks bebas, mis. "Q3 2026" — gak ada format baku.'],
            'target' => ['required' => true],
            'capaian' => ['required' => false, 'note' => 'Kalau kosong, otomatis 0.'],
            'satuan' => ['required' => false],
            'bobot' => ['required' => false, 'note' => 'Angka 0-100 (persen).'],
            'tenggat' => ['required' => false, 'note' => 'Boleh kosong. Format DD/MM/YYYY.'],
            'status' => ['required' => false, 'note' => 'Kalau kosong, otomatis "Active". Isi salah satu: Active, Completed, Archived.'],
            'catatan_owner' => ['required' => false],
        ];
    }

    public function rules(): array
    {
        return [
            'karyawan' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (! $this->findEmployee($value)) {
                        $fail('Karyawan tidak ditemukan — isi nama atau email karyawan yang sudah terdaftar.');
                    }
                },
            ],
            'judul_kpi' => ['required', 'string', 'max:150'],
            'periode' => ['required', 'string', 'max:50'],
            'target' => ['required', 'numeric', 'min:0'],
            'capaian' => ['nullable', 'numeric', 'min:0'],
            'satuan' => ['nullable', 'string', 'max:30'],
            'bobot' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tenggat' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value !== null && $value !== '' && $this->parseDate($value) === null) {
                        $fail('Tenggat harus tanggal yang valid (format DD/MM/YYYY).');
                    }
                },
            ],
            'status' => ['nullable', Rule::in(Kpi::STATUSES)],
            'catatan_owner' => ['nullable', 'string'],
        ];
    }

    public function mapRow(array $validated): array
    {
        $employee = $this->findEmployee($validated['karyawan']);

        return [
            'employee_id' => $employee?->id,
            'title' => $validated['judul_kpi'],
            'period' => $validated['periode'],
            'target' => (float) $validated['target'],
            // 0 = default kolom `current` di migration, ditulis eksplisit
            // biar preview nampilin angka yang beneran bakal kesimpen.
            'current' => $validated['capaian'] !== '' && $validated['capaian'] !== null ? (float) $validated['capaian'] : 0.0,
            'unit' => $validated['satuan'] ?: null,
            'weight' => $validated['bobot'] !== '' && $validated['bobot'] !== null ? (float) $validated['bobot'] : null,
            'due_date' => $this->parseDate($validated['tenggat'] ?? null),
            'status' => ! empty($validated['status']) ? $validated['status'] : 'Active',
            'owner_note' => $validated['catatan_owner'] ?? null,
            // Display-only buat preview (diabaikan Eloquent pas
            // mass-assign) — sama trik kayak project_name/pic_name di
            // WorkItemImport.
            'employee_name' => $employee?->name,
        ];
    }

    private function findEmployee(string $value): ?User
    {
        return User::query()
            ->where('name', $value)
            ->orWhere('email', $value)
            ->first();
    }
}