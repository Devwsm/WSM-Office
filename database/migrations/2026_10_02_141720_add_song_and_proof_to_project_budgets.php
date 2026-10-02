<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * add_song_and_proof_to_project_budgets
 * ---------------------------------------------------------------------
 * Padanan 2 kolom budget line di prototype v22: "Linked Song P&L" dan
 * "Payment Proof Link".
 *
 * `song_title` SENGAJA teks bebas, bukan foreign key: di project ini belum
 * ada entitas lagu (Royalty hanya `royalty_entries` dengan judul teks;
 * subsistem royaltyFinance/songs prototype memang tidak dibangun, lihat
 * catatan migration royalty_entries). Pengelompokan "Per Lagu" mencocokkan
 * judul tanpa membedakan huruf besar/kecil.
 *
 * Aditif & nullable: baris budget yang sudah ada di production tidak
 * berubah dan tetap valid.
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_budgets', function (Blueprint $table) {
            $table->string('song_title', 150)->nullable()->after('item');
            $table->string('proof_link', 500)->nullable()->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('project_budgets', function (Blueprint $table) {
            $table->dropColumn(['song_title', 'proof_link']);
        });
    }
};