<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Merapikan Audit Log yang SUDAH tercatat supaya sejalan dengan aturan privasi
 * baru (2026-10-11). Hanya mengubah data lama; tidak mengubah struktur tabel.
 *
 *  1. Hapus baris "Login berhasil" (jam login terakhir sudah ada di Monitor Login).
 *  2. Kosongkan alamat IP pada baris selain kejadian keamanan.
 *  3. "Login gagal" untuk email tidak terdaftar: buang teks email yang diketik.
 *  4. Isi catatan task di baris lama diganti "Catatan: diubah".
 *
 * TIDAK BISA DIBATALKAN: data yang dihapus atau dikosongkan tidak dipulihkan
 * oleh down(). Buat backup database sebelum deploy bila perlu.
 */
return new class extends Migration
{
    /** Salinan AuditLog::IP_ACTIONS, ditulis ulang supaya migrasi tidak bergantung pada kode model. */
    private const IP_ACTIONS = ['Login gagal', 'Password direset', 'Password diganti', 'Dashboard access diubah'];

    public function up(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        DB::table('audit_logs')->where('action', 'Login berhasil')->delete();

        DB::table('audit_logs')
            ->whereNotNull('ip_address')
            ->whereNotIn('action', self::IP_ACTIONS)
            ->update(['ip_address' => null]);

        DB::table('audit_logs')
            ->where('action', 'Login gagal')
            ->where('detail', 'like', '%(email tidak terdaftar)%')
            ->update(['detail' => 'Percobaan login gagal: email tidak terdaftar.']);

        DB::table('audit_logs')
            ->where('detail', 'like', '%Catatan: %')
            ->where(function ($q) {
                $q->where('action', 'like', 'Task%')
                    ->orWhere('action', 'like', 'Catatan task%')
                    ->orWhere('action', 'like', 'Progress task%');
            })
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $clean = preg_replace('/Catatan: .*?(?=; Link: |\.\s*$|$)/su', 'Catatan: diubah', (string) $row->detail);

                    if ($clean !== null && $clean !== $row->detail) {
                        DB::table('audit_logs')->where('id', $row->id)->update(['detail' => $clean]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Sengaja kosong: data yang dibersihkan tidak bisa dipulihkan.
    }
};