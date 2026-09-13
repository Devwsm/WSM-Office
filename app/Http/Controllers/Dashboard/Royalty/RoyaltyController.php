<?php

namespace App\Http\Controllers\Dashboard\Royalty;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\Royalty\RoyaltyRequest;
use App\Models\RoyaltyEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * RoyaltyController (Dashboard > Royalty Dashboard)
 * ---------------------------------------------------------------------
 * Fase 13 — CRUD royalty entry (gross, share%, recoupment, status
 * pembayaran), padanan `saveRoyaltyEntry()` di prototype v18. Net
 * payable dihitung lewat `RoyaltyEntry::net()` (gross * share% -
 * recoup) — bukan kolom tersimpan, biar selalu konsisten kalau
 * gross/share/recoup diedit belakangan.
 *
 * Gate 'view'/'manage' modul 'royalty' — sama pola persis modul lain.
 * ---------------------------------------------------------------------
 */
class RoyaltyController extends Controller
{
    public function index(Request $request)
    {
        $selectedStatus = $request->string('status')->toString() ?: null;

        $entries = RoyaltyEntry::query()
            ->with('updater')
            ->when($selectedStatus, fn($q) => $q->where('status', $selectedStatus))
            ->orderByDesc('period')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('dashboard.royalty.index', [
            'entries' => $entries,
            'selectedStatus' => $selectedStatus,
        ]);
    }

    public function create()
    {
        return view('dashboard.royalty.create');
    }

    public function store(RoyaltyRequest $request)
    {
        $data = $request->validated();
        $data['updated_by'] = Auth::id();

        RoyaltyEntry::create($data);

        return redirect()->route('dashboard.royalty.index')->with('status', 'Royalty entry berhasil ditambahkan.');
    }

    public function edit(RoyaltyEntry $royalty)
    {
        return view('dashboard.royalty.edit', ['royalty' => $royalty]);
    }

    public function update(RoyaltyRequest $request, RoyaltyEntry $royalty)
    {
        $data = $request->validated();
        $data['updated_by'] = Auth::id();

        $royalty->update($data);

        return redirect()->route('dashboard.royalty.index')->with('status', 'Royalty entry berhasil diperbarui.');
    }

    public function destroy(RoyaltyEntry $royalty)
    {
        $royalty->delete();

        return back()->with('status', 'Royalty entry berhasil dihapus.');
    }
}