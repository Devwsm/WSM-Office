<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Model TeamGroup
 * ---------------------------------------------------------------------
 * Kelompok tim custom (mis. "WS TEAM", "OPERATING TEAM") — padanan
 * `state.teamGroupsV24` di prototype. Satu karyawan boleh ada di lebih
 * dari satu kelompok. Dipakai sebagai pilihan visibility project
 * (`projects.visibility` = "group:<id>"), lihat Project::visibilityOptions().
 * Beda dari `users.work_team` (tim kerja bawaan satu-orang-satu-tim).
 * ---------------------------------------------------------------------
 */
#[Fillable(['name', 'color'])]
class TeamGroup extends Model
{
    public const DEFAULT_COLOR = '#DCE8FF';

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_group_user')->orderBy('users.name');
    }

    /** Nilai `projects.visibility` untuk kelompok ini. */
    public function visibilityKey(): string
    {
        return 'group:' . $this->id;
    }

    /** Berapa project yang masih membatasi visibility-nya ke kelompok ini. */
    public function projectsUsingCount(): int
    {
        return Project::query()->where('visibility', $this->visibilityKey())->count();
    }
}