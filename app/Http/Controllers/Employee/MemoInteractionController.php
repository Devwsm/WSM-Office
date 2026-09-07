<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Memo\ReplyMemoThreadRequest;
use App\Models\Memo;
use App\Models\MemoThreadMessage;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * MemoInteractionController (Employee)
 * ---------------------------------------------------------------------
 * Fase 8 — Memo Forum, sisi kartu "Info dari Owner" di Home. Padanan
 * `setMemoReadV18`/`toggleMemoHiddenV18`/`submitMemoThreadEmployeeV18`
 * di prototype v18. SENGAJA di grup role yang sama kayak
 * `employee.*` (karyawan,manajer,owner,hrd) — Memo Forum ini
 * pengumuman ke SEMUA tim, bukan modul kerja yang butuh dashboard_access
 * (sama alasan seperti kartu Home yang udah ada, lihat HomeController).
 * ---------------------------------------------------------------------
 */
class MemoInteractionController extends Controller
{
    public function toggleRead(Memo $memo)
    {
        /** @var User $me */
        $me = Auth::user();
        $state = $memo->readStateFor($me);
        $state->read_at = $state->read_at ? null : now();
        $state->save();

        return back();
    }

    public function toggleHidden(Memo $memo)
    {
        /** @var User $me */
        $me = Auth::user();
        $state = $memo->readStateFor($me);
        $state->hidden_at = $state->hidden_at ? null : now();
        $state->save();

        return back()->with('status', $state->hidden_at ? 'Memo disembunyikan.' : 'Memo ditampilkan lagi.');
    }

    public function reply(ReplyMemoThreadRequest $request, Memo $memo)
    {
        /** @var User $me */
        $me = Auth::user();

        MemoThreadMessage::create([
            'memo_id' => $memo->id,
            'user_id' => $me->id,
            'message' => $request->validated('message'),
        ]);

        // Ngirim reply otomatis nandain memo-nya sendiri udah dibaca —
        // sama kayak submitMemoThreadEmployeeV18() di prototype
        // (writeMemoInboxState(...,{read:true})).
        $state = $memo->readStateFor($me);
        if (! $state->read_at) {
            $state->read_at = now();
            $state->save();
        }

        return back()->with('status', 'Reply terkirim.');
    }
}