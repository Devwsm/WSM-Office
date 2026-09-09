<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\WorkItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * WorkTrackerController (Employee)
 * ---------------------------------------------------------------------
 * App Mode (2026-09-09) — padanan `employeeTasksMarkup()` (My Work
 * Tracker, embedded di Home, lihat HomeController::index() &
 * employee/_work-tracker.blade.php) dan `openSharedWorkloadCalendar()`
 * (Shared Calendar, `calendar()` di bawah) di prototype v32.
 *
 * KOREKSI dari audit sebelumnya (README ronde 5): versi prototype
 * PALING AKHIR (v18/v32, baris ~1857-1858 di
 * WOS_2_0_STANDALONE_v32.html) TERNYATA gak punya filter Project/
 * Category/Progress di widget Home sama sekali — itu klaim yang salah
 * dari audit sebelumnya (ketuker sama versi v12 yang udah digantikan).
 * Widget Home versi final cuma nampilin item open (max 8) + tombol
 * "Shared Calendar", TANPA filter apa pun. Shared Calendar versi final
 * juga TANPA filter Project/PIC (klaim itu juga salah, gak pernah ada
 * di kode manapun yang ketemu). Diimplementasikan di sini PERSIS versi
 * final itu, BUKAN versi ber-filter yang disebut di audit sebelumnya.
 *
 * "My Work Tracker" = WorkItem dengan `pic_employee_id` = user yang
 * login. `additional_pic` (kolom string bebas, BUKAN FK) sengaja TIDAK
 * ikut nentuin "punya siapa" — gak bisa dicocokkan ke user_id manapun,
 * cuma teks tambahan buat dibaca manusia.
 *
 * SCOPE YANG SENGAJA BELUM DIKERJAKAN (v1, lihat README): update
 * progress/notes langsung dari Home (prototype punya dropdown ubah
 * status + tombol "Update Note" di tiap task-card — lihat
 * `taskCardMarkupV9`, parameter `employeeModeView=true`). Ini VIEW-ONLY
 * dulu — nulis/ubah WorkItem butuh route+validasi+authorization
 * terpisah yang belum digarap, supaya gak nyampur sama quick win Team
 * Moments/Paid Leave yang murni read-only dari data yang udah ada.
 * ---------------------------------------------------------------------
 */
class WorkTrackerController extends Controller
{
    /**
     * "Shared Calendar" — padanan `openSharedWorkloadCalendar()`.
     * Prototype nampilinnya sebagai modal (SPA, sekali render penuh di
     * client). WSM-Office adalah aplikasi multi-page (bukan SPA) —
     * diadaptasi jadi halaman tersendiri (bukan modal overlay), pola
     * yang sama seperti adaptasi "Kunci Dashboard" (dulu modal/
     * sessionStorage di prototype, sekarang halaman + session
     * server-side di sini). Fungsinya identik: 14 hari ke depan
     * (termasuk hari ini), semua WorkItem seluruh TIM yang due di
     * rentang itu dan belum Done — bukan cuma milik sendiri, makanya
     * namanya "Shared".
     */
    public function calendar()
    {
        $start = Carbon::today();
        $days = collect(range(0, 13))->map(fn(int $i) => $start->copy()->addDays($i));

        $items = WorkItem::query()
            ->whereBetween('due_date', [$start->toDateString(), $start->copy()->addDays(13)->toDateString()])
            ->where('progress', '!=', 'Done')
            ->with(['pic', 'project'])
            ->get()
            ->groupBy(fn(WorkItem $item) => $item->due_date->toDateString());

        return view('employee.work-tracker.calendar', [
            'days' => $days,
            'items' => $items,
            'meId' => Auth::id(),
        ]);
    }
}