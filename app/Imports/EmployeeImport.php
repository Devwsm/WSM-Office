<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * EmployeeImport — Batch 4.
 * Dipakai controller: App\Http\Controllers\Dashboard\ExportImport\ImportController
 * Catalog key: 'employees' (owner_only — lihat App\Support\ExportImport\ExportCatalog)
 *
 * BEDA dari WorkItemImport (Batch 3): baris valid di sini bikin AKUN
 * LOGIN baru (User::create(), kena #[Hidden]/'hashed' cast di model),
 * bukan cuma insert data biasa — makanya butuh keputusan tambahan yang
 * sudah difinalkan Owner (lihat README, bagian "Rencana Batch 4"):
 *   - password BOLEH kosong, default ke literal "password" (BUKAN
 *     random per-orang, biar konsisten sama akun demo/testing lain).
 *   - role diisi langsung per baris (bukan default 'karyawan').
 *   - manager_id (atasan) SENGAJA TIDAK ada di template — diisi
 *     manual belakangan lewat halaman edit karyawan yang sudah ada.
 *   - email yang UDAH KEPAKE (di DB ATAU dobel di file yang sama)
 *     ditolak sebagai baris error, BUKAN update/timpa data lama.
 */
class EmployeeImport extends BaseImport
{
    /**
     * Email yang sudah "dipesan" baris sebelumnya DI FILE YANG SAMA
     * (huruf kecil semua buat perbandingan) — soalnya `unique:users`
     * cuma ngecek ke DB, dua baris dengan email baru yang SAMA di 1
     * file bakal sama-sama lolos rules() kalau gak dijaga di sini,
     * terus baris ke-2 bakal gagal pas User::create() (DB constraint)
     * pas commit — sudah kelewat jauh, gak kelihatan di preview.
     * Baris ke-2+ dengan email yang sama sengaja ditandai error di
     * sini juga, walau baris pertamanya sendiri belum tentu valid
     * (field lain di baris pertama mungkin gagal) — daripada dua
     * baris rebutan 1 email yang sama-sama "kelihatan sah".
     *
     * @var array<int, string>
     */
    private array $seenEmails = [];

    public function templateHeadings(): array
    {
        return [
            'nama',
            'email',
            'password',
            'role',
            'divisi',
            'jabatan',
            'tanggal_masuk',
            'jatah_cuti',
            'tanggal_lahir',
            'gaji_pokok',
            'target_jam_per_hari',
            'tarif_lembur_flat',
        ];
    }

    public function previewColumns(): array
    {
        return [
            ['label' => 'Nama', 'key' => 'name'],
            ['label' => 'Email', 'key' => 'email'],
            ['label' => 'Role', 'key' => 'role'],
            ['label' => 'Password', 'key' => 'password_source'],
            ['label' => 'Divisi', 'key' => 'division', 'fallback' => '-'],
            ['label' => 'Jabatan', 'key' => 'job_title', 'fallback' => '-'],
            ['label' => 'Tgl Masuk', 'key' => 'join_date', 'type' => 'date', 'fallback' => '-'],
            ['label' => 'Jatah Cuti', 'key' => 'annual_leave_entitlement'],
        ];
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'string',
                'email',
                'max:150',
                Rule::unique('users', 'email'),
                function ($attribute, $value, $fail) {
                    $key = mb_strtolower(trim((string) $value));
                    if (in_array($key, $this->seenEmails, true)) {
                        $fail('Email ini juga muncul di baris lain pada file yang sama.');

                        return;
                    }
                    $this->seenEmails[] = $key;
                },
            ],
            // Min 8 sengaja disamain ke StoreEmployeeRequest (form
            // manual) — kebetulan pas juga sama panjang default
            // "password" (8 huruf), jadi fallback-nya sendiri selalu
            // lolos rule ini.
            'password' => ['nullable', 'string', 'min:8'],
            'role' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (! in_array(mb_strtolower(trim((string) $value)), ['owner', 'manajer', 'karyawan', 'hrd'], true)) {
                        $fail('Role harus salah satu dari: owner, manajer, karyawan, hrd.');
                    }
                },
            ],
            'divisi' => ['nullable', 'string', 'max:100'],
            'jabatan' => ['nullable', 'string', 'max:100'],
            'tanggal_masuk' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value !== null && $value !== '' && $this->parseDate($value) === null) {
                        $fail('Tanggal masuk harus tanggal yang valid (format DD/MM/YYYY).');
                    }
                },
            ],
            'jatah_cuti' => ['nullable', 'integer', 'min:0', 'max:60'],
            'tanggal_lahir' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value === null || $value === '') {
                        return;
                    }
                    $parsed = $this->parseDate($value);
                    if ($parsed === null) {
                        $fail('Tanggal lahir harus tanggal yang valid (format DD/MM/YYYY).');
                    } elseif (Carbon::parse($parsed)->isAfter(Carbon::today())) {
                        $fail('Tanggal lahir tidak boleh di masa depan.');
                    }
                },
            ],
            'gaji_pokok' => ['nullable', 'numeric', 'min:0'],
            'target_jam_per_hari' => ['nullable', 'integer', 'min:1', 'max:24'],
            'tarif_lembur_flat' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function mapRow(array $validated): array
    {
        return [
            'name' => $validated['nama'],
            'email' => $validated['email'],
            // Cast 'hashed' di model User yang urus bcrypt-nya pas
            // disimpan — di sini cukup teks polos.
            'password' => ! empty($validated['password']) ? $validated['password'] : 'password',
            'role' => mb_strtolower(trim($validated['role'])),
            'division' => $validated['divisi'] ?: null,
            'job_title' => $validated['jabatan'] ?: null,
            'join_date' => $this->parseDate($validated['tanggal_masuk'] ?? null),
            // 12 = default kolom `annual_leave_entitlement` di migration
            // — ditulis eksplisit di sini (bukan diandalkan ke DB
            // default) biar preview nampilin angka yang beneran bakal
            // kesimpen, bukan kosong.
            'annual_leave_entitlement' => $validated['jatah_cuti'] !== '' && $validated['jatah_cuti'] !== null
                ? (int) $validated['jatah_cuti']
                : 12,
            'birth_date' => $this->parseDate($validated['tanggal_lahir'] ?? null),
            'salary_base' => $validated['gaji_pokok'] !== '' && $validated['gaji_pokok'] !== null ? (float) $validated['gaji_pokok'] : null,
            'target_hours_per_day' => $validated['target_jam_per_hari'] !== '' && $validated['target_jam_per_hari'] !== null ? (int) $validated['target_jam_per_hari'] : null,
            'flat_overtime_rate' => $validated['tarif_lembur_flat'] !== '' && $validated['tarif_lembur_flat'] !== null ? (float) $validated['tarif_lembur_flat'] : null,
            // Display-only buat preview (diabaikan Eloquent pas
            // mass-assign, sama trik kayak project_name/pic_name di
            // WorkItemImport) — biar Owner bisa lihat di preview mana
            // baris yang bakal kena default "password" tanpa nampilin
            // teks password beneran di layar.
            'password_source' => ! empty($validated['password']) ? 'Diisi manual' : 'Default (password)',
        ];
    }

    /** Sama persis WorkItemImport::parseDate() — lihat komentar di sana buat alasannya. */
    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, trim((string) $value));
                if ($date !== false) {
                    return $date->format('Y-m-d');
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }
}