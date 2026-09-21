<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * add_developer_role_and_must_change_password_to_users
 * ---------------------------------------------------------------------
 * 1. Role baru `developer` (akses Tingkat 2: seperti Owner, tetapi tidak
 *    boleh menyentuh akun Owner dan tidak boleh membuka Dashboard
 *    Access). Aturan lengkapnya ada di App\Models\User (isDeveloper(),
 *    canManageAccount(), assignableRoles()).
 * 2. Kolom `must_change_password` — kalau menyala, user dipaksa ganti
 *    password sebelum bisa memakai halaman lain (lihat middleware
 *    EnsurePasswordChanged). Dinyalakan saat password direset lewat
 *    dashboard IT dan saat akun dibuat lewat import dengan password
 *    default.
 *
 * Enum diganti lewat `Schema::table()->enum()->change()` (portabel
 * MySQL/MariaDB dan SQLite), sama pola migrasi enum `dashboard_access`.
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(false)->after('password');
        });

        $this->setRoles(['owner', 'manajer', 'karyawan', 'hrd', 'developer']);
    }

    public function down(): void
    {
        // Akun ber-role developer harus dipindah/dihapus dulu sebelum
        // rollback, kalau tidak MySQL menolak nilai yang tidak ada di enum.
        $this->setRoles(['owner', 'manajer', 'karyawan', 'hrd']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('must_change_password');
        });
    }

    /** @param  list<string>  $roles */
    private function setRoles(array $roles): void
    {
        Schema::table('users', function (Blueprint $table) use ($roles) {
            $table->enum('role', $roles)->default('karyawan')->change();
        });
    }
};