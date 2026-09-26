<?php

namespace App\Http\Controllers\Dashboard\Work;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\Work\WorkReminderRequest;
use App\Models\Memo;
use App\Models\WorkItem;
use Illuminate\Support\Facades\Auth;

/**
 * WorkReminderController (Dashboard > Work Control)
 * ---------------------------------------------------------------------
 * README Bab 2.1 #28 / Bab 4.2 no. 9 — "Assign / Reminder dari
 * dashboard". Padanan sendAdminReminder() / sendAdminDashboardReminder()
 * di prototype v32 (secretary-console & Work Dashboard > "Memo &
 * Reminder"): satu form ("Kirim Reminder" di dashboard/work/index),
 * satu submit, bikin 2 record —
 *   1. WorkItem baru: is_reminder=true, section='REMINDER / ADMIN',
 *      progress='Pending', PIC = karyawan yang dituju. Muncul di board
 *      Work Tracker (WorkTrackerBoardController) sama kayak task biasa
 *      — jadi otomatis "assign task" sekaligus, bukan cuma notifikasi.
 *   2. Memo baru: audience='tertentu', recipient = karyawan yang sama,
 *      isi otomatis dari judul + due date + catatan. Muncul di kartu
 *      "Info dari Owner" App Mode karyawan itu (HomeController) dan di
 *      Inbox-nya — padanan `state.memos.push(...)` yang prototype
 *      jalankan bareng `state.tasks.push(...)` di fungsi yang sama.
 *
 * Sengaja controller sendiri (bukan ditambahin ke MemoController atau
 * WorkTrackerBoardController) karena ini satu-satunya aksi di modul
 * 'work' yang NULIS DUA MODEL SEKALIGUS — controller lain di modul ini
 * masing-masing cuma pegang 1 model, biar gak nyampur concern (pola
 * "self-contained controller" yang dipakai di seluruh app ini).
 *
 * Blast Memo ke banyak orang TETAP lewat MemoController yang sudah
 * ada (form "+ Tambah" biasa) — form reminder ini khusus kasus
 * "1 task + 1 notifikasi ke 1 orang", bukan pengganti Memo Forum.
 *
 * item_no SENGAJA dibiarkan null (kolom nullable) — nomor urut section
 * cuma relevan buat board yang dikelola manual dari WorkTrackerBoard-
 * Controller; reminder dari sini gak perlu ikut penomoran itu.
 * ---------------------------------------------------------------------
 */
class WorkReminderController extends Controller
{
    public function store(WorkReminderRequest $request)
    {
        $data = $request->validated();

        $item = WorkItem::create([
            'section' => 'REMINDER / ADMIN',
            'title' => $data['title'],
            'due_date' => $data['due_date'] ?? null,
            'pic_employee_id' => $data['pic_employee_id'],
            'progress' => 'Pending',
            'priority' => $data['priority'],
            'notes' => $data['notes'] ?? null,
            'is_reminder' => true,
            'created_by' => Auth::id(),
        ]);

        $dueText = $item->due_date ? ' · due ' . $item->due_date->translatedFormat('d M Y') : '';
        $content = $data['title'] . $dueText;
        if (! empty($data['notes'])) {
            $content .= "\n\n{$data['notes']}";
        }

        $memo = Memo::create([
            'type' => 'memo',
            'title' => 'Work Reminder',
            'content' => $content,
            'pinned' => false,
            'audience' => 'tertentu',
            'active' => true,
            'created_by' => Auth::id(),
        ]);
        $memo->recipients()->attach($data['pic_employee_id']);

        return back()->with('status', 'Reminder dikirim: masuk Work Tracker & Info dari Owner karyawan itu.');
    }
}