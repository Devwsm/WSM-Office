<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lembur yang sudah ditolak/dibatalkan harus bisa diajukan lagi di tanggal
 * yang sama.
 *
 * Migrasi awal `overtime_requests` memasang UNIQUE(user_id, date), padahal
 * validasi form (StoreOvertimeRequestRequest) hanya menolak pengajuan yang
 * masih AKTIF (pending/disetujui). Akibatnya pengajuan ulang setelah ditolak
 * atau dibatalkan lolos validasi lalu meledak jadi error 500 di database.
 *
 * Aturan "satu pengajuan aktif per tanggal" tetap dijaga di validasi form dan
 * di tombol Setujui (lihat Approval\OvertimeRequestController); payroll
 * menghitung tanggal lembur berbeda (bukan jumlah baris), jadi dua baris di
 * tanggal yang sama tidak bisa menggandakan uang lembur.
 *
 * Urutan penting di MySQL: foreign key `user_id` butuh sebuah index, jadi
 * index biasa dibuat DULU sebelum unique-nya dilepas (kalau terbalik, MySQL
 * menolak dengan error 1553).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('overtime_requests', function (Blueprint $table) {
            $table->index(['user_id', 'date'], 'overtime_requests_user_id_date_index');
        });

        Schema::table('overtime_requests', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::table('overtime_requests', function (Blueprint $table) {
            $table->unique(['user_id', 'date']);
        });

        Schema::table('overtime_requests', function (Blueprint $table) {
            $table->dropIndex('overtime_requests_user_id_date_index');
        });
    }
};