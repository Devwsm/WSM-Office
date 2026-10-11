<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;

/**
 * Hapus Audit Log yang lebih tua dari `config('audit.retention_months')` bulan.
 * Pembersihan juga berjalan sendiri saat halaman Audit Log dibuka (maksimal
 * sekali sehari), jadi perintah ini hanya dibutuhkan kalau hosting punya cron.
 */
class PruneAuditLogs extends Command
{
    protected $signature = 'audit:prune';

    protected $description = 'Hapus Audit Log yang lebih tua dari masa simpan (config audit.retention_months)';

    public function handle(): int
    {
        $deleted = AuditLog::pruneExpired();

        $this->info("{$deleted} baris Audit Log lama dihapus.");

        return self::SUCCESS;
    }
}