<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * create_work_item_additional_pics_table
 * ---------------------------------------------------------------------
 * 2026-09-30 — PIC lebih dari satu orang per task, padanan `additionalPicIds`
 * (maksimal 2 tambahan, jadi total 3 PIC) di prototype v19+.
 *
 * `work_items.pic_employee_id` TETAP jadi PIC 1 / PIC utama — semua query
 * lama (Home karyawan, kalender, Team Overview, export) tetap benar tanpa
 * diubah. Tabel pivot ini hanya menampung PIC 2 & PIC 3 sebagai relasi
 * SUNGGUHAN ke `users`, supaya orangnya ikut melihat task itu di daftar
 * kerjanya sendiri (bukan cuma teks bebas seperti `additional_pic`).
 *
 * Kolom teks `work_items.additional_pic` SENGAJA tidak dihapus: masih dipakai
 * penanda "ALL TEAM" dari action item MoM, dan sebagai catatan bebas untuk
 * pihak luar yang bukan user (mis. "Ikhbal WS Team", "Vendor").
 *
 * Data lama: nama di `additional_pic` yang PERSIS sama dengan nama satu user
 * (dipisah koma, "&", "/" atau "·") dipindah jadi PIC tambahan. Nama yang
 * tidak cocok persis dibiarkan sebagai teks — tidak ada tebakan.
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_item_additional_pics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_item_id')->constrained('work_items')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['work_item_id', 'user_id']);
            $table->index('user_id');
        });

        $this->backfillFromFreeText();
    }

    public function down(): void
    {
        Schema::dropIfExists('work_item_additional_pics');
    }

    private function backfillFromFreeText(): void
    {
        $usersByName = DB::table('users')->get(['id', 'name'])
            ->groupBy(fn($u) => mb_strtolower(trim($u->name)));

        $now = now();

        DB::table('work_items')
            ->whereNotNull('additional_pic')
            ->where('additional_pic', '!=', '')
            ->orderBy('id')
            ->each(function ($item) use ($usersByName, $now) {
                $names = preg_split('/\s*(?:,|&|\/|·|\bdan\b)\s*/iu', (string) $item->additional_pic) ?: [];

                $ids = collect($names)
                    ->map(fn($n) => mb_strtolower(trim($n)))
                    ->filter()
                    ->map(fn($n) => $usersByName->get($n))
                    // Hanya nama yang cocok dengan TEPAT satu user (nama kembar = ambigu, dilewati).
                    ->filter(fn($matches) => $matches && $matches->count() === 1)
                    ->map(fn($matches) => (int) $matches->first()->id)
                    ->reject(fn($id) => $id === (int) $item->pic_employee_id)
                    ->unique()
                    ->take(2)
                    ->values();

                foreach ($ids as $userId) {
                    DB::table('work_item_additional_pics')->insert([
                        'work_item_id' => $item->id,
                        'user_id' => $userId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            });
    }
};