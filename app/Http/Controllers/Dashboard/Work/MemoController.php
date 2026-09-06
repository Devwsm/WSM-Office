<?php

namespace App\Http\Controllers\Dashboard\Work;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\Work\MemoRequest;
use App\Models\Memo;
use Illuminate\Support\Facades\Auth;

/**
 * MemoController
 * ---------------------------------------------------------------------
 * Fase 6b — modul pertama yang jalan di atas fondasi Fase 6a. Akses
 * dijaga per-route lewat middleware 'module:work,view' (index) dan
 * 'module:work,manage' (create/store/edit/update/destroy) di
 * routes/web.php — bukan dicek manual di sini, biar konsisten sama
 * pola middleware 'role' yang sudah ada.
 * ---------------------------------------------------------------------
 */
class MemoController extends Controller
{
    public function index()
    {
        $memos = Memo::query()->with('creator')->latestFirst()->paginate(15);

        return view('dashboard.work.index', ['memos' => $memos]);
    }

    public function create()
    {
        return view('dashboard.work.create');
    }

    public function store(MemoRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = Auth::id();
        $data['pinned'] = $request->boolean('pinned');

        Memo::create($data);

        return redirect()->route('dashboard.work.index')->with('status', 'Memo/MoM berhasil ditambahkan.');
    }

    public function edit(Memo $memo)
    {
        return view('dashboard.work.edit', ['memo' => $memo]);
    }

    public function update(MemoRequest $request, Memo $memo)
    {
        $data = $request->validated();
        $data['pinned'] = $request->boolean('pinned');

        $memo->update($data);

        return redirect()->route('dashboard.work.index')->with('status', 'Memo/MoM berhasil diperbarui.');
    }

    public function destroy(Memo $memo)
    {
        $memo->delete();

        return back()->with('status', 'Memo/MoM berhasil dihapus.');
    }
}