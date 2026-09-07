<?php

namespace App\Http\Controllers\Dashboard\Work;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\Work\MemoRequest;
use App\Http\Requests\Memo\ReplyMemoThreadRequest;
use App\Models\Memo;
use App\Models\MemoThreadMessage;
use Illuminate\Support\Facades\Auth;

/**
 * MemoController
 * ---------------------------------------------------------------------
 * Fase 6b — modul pertama yang jalan di atas fondasi Fase 6a. Akses
 * dijaga per-route lewat middleware 'module:work,view' (index) dan
 * 'module:work,manage' (create/store/edit/update/destroy/reply) di
 * routes/web.php — bukan dicek manual di sini, biar konsisten sama
 * pola middleware 'role' yang sudah ada.
 *
 * Fase 8 nambah thread reply per memo + tanda-baca manajemen (badge
 * unread di sidebar "Work Control"). BEDA dari prototype v18 (badge-nya
 * itung SEMUA pesan employee dari awal waktu, gak pernah reset — lihat
 * catatan di migration memo_thread_messages): di sini badge cuma
 * itung yang BELUM ditandai `read_by_management_at`, dan ke-set
 * otomatis pas index() ini dibuka.
 * ---------------------------------------------------------------------
 */
class MemoController extends Controller
{
    public function index()
    {
        $memos = Memo::query()
            ->with(['creator', 'threadMessages', 'reads' => fn($q) => $q->where('user_id', Auth::id())])
            ->latestFirst()
            ->paginate(15);

        // Tanda-baca manajemen: siapa pun yang manage-level modul 'work'
        // dan buka halaman ini otomatis "menandai" semua reply karyawan
        // yang masih belum dibaca — semua thread udah kelihatan penuh di
        // sini, jadi wajar dianggap "udah dilihat manajemen" begitu
        // halaman ini dimuat. Badge unread-nya sendiri dihitung lewat
        // MemoThreadMessage::unreadForManagementCount(), dipanggil
        // langsung dari layouts/app.blade.php (bukan lewat controller
        // ini — itemnya nempel di sidebar, bukan di halaman ini).
        MemoThreadMessage::query()
            ->whereIn('memo_id', $memos->pluck('id'))
            ->whereNull('read_by_management_at')
            ->update(['read_by_management_at' => now()]);

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

    /**
     * Reply thread dari sisi manajemen. Route-nya SENGAJA butuh
     * 'module:work,manage' (bukan 'view') — beda dari
     * Employee\MemoInteractionController::reply() yang kebuka buat
     * SEMUA role internal (karena itu reply dari karyawan biasa,
     * bukan balasan resmi manajemen).
     */
    public function reply(ReplyMemoThreadRequest $request, Memo $memo)
    {
        MemoThreadMessage::create([
            'memo_id' => $memo->id,
            'user_id' => Auth::id(),
            'message' => $request->validated('message'),
            // Balasan manajemen sendiri gak perlu nunggu "dibaca
            // manajemen" — langsung ke-mark biar gak nambah badge
            // unread ke diri sendiri.
            'read_by_management_at' => now(),
        ]);

        return back()->with('status', 'Reply terkirim.');
    }
}