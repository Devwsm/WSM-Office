<?php

namespace App\Http\Controllers\Dashboard\Work;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\Work\MemoRequest;
use App\Http\Requests\Memo\ReplyMemoThreadRequest;
use App\Models\Memo;
use App\Models\MemoThreadMessage;
use App\Models\User;
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
            ->with(['creator', 'threadMessages', 'recipients:id,name', 'reads' => fn($q) => $q->where('user_id', Auth::id())])
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

        return view('dashboard.work.index', [
            'memos' => $memos,
            // 2026-09-26 — buat form "Kirim Reminder" (README #28) di
            // atas listing ini; sama data yang dipakai create()/edit().
            'employees' => User::query()->orderBy('name')->get(['id', 'name', 'division']),
        ]);
    }

    public function create()
    {
        return view('dashboard.work.create', [
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'division']),
        ]);
    }

    public function store(MemoRequest $request)
    {
        $data = $request->validated();
        $recipients = $data['recipients'] ?? [];
        unset($data['recipients']);
        $data['created_by'] = Auth::id();
        $data['pinned'] = $request->boolean('pinned');

        $memo = Memo::create($data);

        // 2026-09-16 — recipients cuma kepake kalau audience='tertentu',
        // tapi tetap di-sync (bukan cuma di-attach) walau kosong, biar
        // kalau audience-nya 'semua' gak ada baris nyangkut di
        // memo_recipients dari percobaan sebelumnya.
        $memo->recipients()->sync($recipients);

        return redirect()->route('dashboard.work.index')->with('status', 'Memo/MoM berhasil ditambahkan.');
    }

    public function edit(Memo $memo)
    {
        $memo->load('recipients:id');

        return view('dashboard.work.edit', [
            'memo' => $memo,
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'division']),
        ]);
    }

    public function update(MemoRequest $request, Memo $memo)
    {
        $data = $request->validated();
        $recipients = $data['recipients'] ?? [];
        unset($data['recipients']);
        $data['pinned'] = $request->boolean('pinned');

        $memo->update($data);
        $memo->recipients()->sync($recipients);

        return redirect()->route('dashboard.work.index')->with('status', 'Memo/MoM berhasil diperbarui.');
    }

    public function destroy(Memo $memo)
    {
        $memo->delete();

        return back()->with('status', 'Memo/MoM berhasil dihapus.');
    }

    /**
     * 2026-09-16 — tombol "Deactivate"/"Aktifkan" (prototype: Deactivate
     * doang, di sini dibikin toggle 2 arah biar bisa diaktifin lagi
     * tanpa buka form Edit). Memo nonaktif TETAP ada di listing
     * manajemen ini, cuma disembunyikan dari kartu "Info dari Owner" App
     * Mode (lihat Memo::scopeActive() & HomeController).
     */
    public function toggleActive(Memo $memo)
    {
        $memo->update(['active' => ! $memo->active]);

        return back()->with('status', $memo->active
            ? 'Memo/MoM diaktifkan lagi — muncul lagi di Home karyawan.'
            : 'Memo/MoM dinonaktifkan — nggak muncul lagi di Home karyawan, tapi masih tersimpan di sini.');
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