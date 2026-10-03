<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\TeamGroupRequest;
use App\Models\AuditLog;
use App\Models\TeamGroup;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * TeamGroupController (Owner/Developer)
 * ---------------------------------------------------------------------
 * Kelompok tim custom — padanan "Group Team Manager" di prototype
 * (saveTeamGroupV24 / deleteTeamGroupV24). Kelompok jadi pilihan Visibility
 * di form project. Menghapus kelompok yang masih dipakai project ditolak
 * (sama seperti prototype) supaya project tidak diam-diam jadi terbuka
 * untuk semua atau malah tidak terlihat siapa pun.
 * ---------------------------------------------------------------------
 */
class TeamGroupController extends Controller
{
    public function index()
    {
        return view('owner.team-groups.index', [
            'groups' => TeamGroup::query()->with('members:id,name')->orderBy('name')->get(),
            'employees' => User::query()->orderBy('name')->get(['id', 'name', 'job_title', 'division']),
        ]);
    }

    public function store(TeamGroupRequest $request)
    {
        $data = $request->validated();
        $group = TeamGroup::create(['name' => trim($data['name']), 'color' => strtolower($data['color'])]);
        $group->members()->sync($data['member_ids'] ?? []);

        $this->audit("Kelompok tim \"{$group->name}\" dibuat");

        return redirect()->route('owner.team-groups.index')->with('status', 'Kelompok tim dibuat.');
    }

    public function update(TeamGroupRequest $request, TeamGroup $group)
    {
        $data = $request->validated();
        $group->update(['name' => trim($data['name']), 'color' => strtolower($data['color'])]);
        $group->members()->sync($data['member_ids'] ?? []);

        $this->audit("Kelompok tim \"{$group->name}\" diubah");

        return redirect()->route('owner.team-groups.index')->with('status', 'Kelompok tim disimpan.');
    }

    public function destroy(TeamGroup $group)
    {
        $used = $group->projectsUsingCount();

        if ($used > 0) {
            return back()->with('error', "Kelompok masih dipakai {$used} project. Ganti Visibility project itu dulu, baru hapus kelompoknya.");
        }

        $name = $group->name;
        $group->delete();
        $this->audit("Kelompok tim \"{$name}\" dihapus");

        return redirect()->route('owner.team-groups.index')->with('status', 'Kelompok tim dihapus.');
    }

    private function audit(string $action): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        AuditLog::record($action, "{$action} oleh {$actor->name}.", $actor);
    }
}