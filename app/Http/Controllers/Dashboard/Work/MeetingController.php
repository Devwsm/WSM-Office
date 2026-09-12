<?php

namespace App\Http\Controllers\Dashboard\Work;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\Work\MeetingRequest;
use App\Models\Memo;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Support\Facades\Auth;

/**
 * MeetingController (Dashboard > Work Control > Rapat & Action Item)
 * ---------------------------------------------------------------------
 * Fase 9 lanjutan — MoM TERSTRUKTUR, padanan `state.meetings`
 * (`saveMom`/`momActionRow`) di prototype v18: attendee beneran (relasi
 * ke `users`, bukan teks bebas), action item per-baris (task + PIC
 * perorangan/ALL TEAM + due date), opsional auto-generate ke Work
 * Tracker.
 *
 * SENGAJA dipisah dari `MemoController` (yang juga punya `type=mom`,
 * dari Fase 6b) — itu MoM RINGKAS (cuma catatan + tanggal + teks
 * peserta bebas, gak ada action item terstruktur), dibangun duluan
 * waktu tabel `meetings`/`meeting_action_items` belum ada. Dua-duanya
 * SENGAJA dipertahankan (bukan salah satu dihapus/gantiin):
 *   - Memo type=mom → buat catatan rapat singkat, cepat ditulis.
 *   - Meeting (controller ini) → buat rapat yang perlu action item
 *     terlacak & ditugaskan, sampai bisa nge-generate task di Work
 *     Tracker otomatis.
 * Blast Summary (lihat blast()) NYAMBUNGIN keduanya: ringkasan Meeting
 * di-push jadi Memo type=mom baru, jadi tetap numpang infrastruktur
 * Memo yang udah ada (tampil di kartu "Info dari Owner" Home, Inbox,
 * badge unread) — bukan bikin jalur notifikasi baru dari nol.
 *
 * Gate 'view'/'manage' modul 'work' sama persis pola MemoController &
 * WorkTrackerBoardController.
 * ---------------------------------------------------------------------
 */
class MeetingController extends Controller
{
    public function index()
    {
        $meetings = Meeting::query()
            ->with(['project', 'creator', 'attendees', 'actionItems.pic'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(15);

        return view('dashboard.work.meetings.index', ['meetings' => $meetings]);
    }

    public function create()
    {
        return view('dashboard.work.meetings.create', $this->formData());
    }

    public function store(MeetingRequest $request)
    {
        $data = $request->validated();
        $attendeeIds = $data['attendees'] ?? [];
        $actionItemsInput = $data['action_items'] ?? [];
        $syncToTracker = $request->boolean('sync_to_tracker');
        unset($data['attendees'], $data['action_items'], $data['sync_to_tracker']);

        $data['created_by'] = Auth::id();

        $meeting = Meeting::create($data);
        $meeting->attendees()->sync($attendeeIds);

        foreach ($actionItemsInput as $row) {
            $this->createActionItem($meeting, $row, $syncToTracker);
        }

        return redirect()->route('dashboard.work.meetings.index')->with('status', 'MoM berhasil ditambahkan.');
    }

    public function show(Meeting $meeting)
    {
        $meeting->load(['project', 'creator', 'attendees', 'actionItems.pic', 'actionItems.workItem']);

        return view('dashboard.work.meetings.show', ['meeting' => $meeting]);
    }

    public function edit(Meeting $meeting)
    {
        $meeting->load(['attendees', 'actionItems']);

        return view('dashboard.work.meetings.edit', $this->formData() + ['meeting' => $meeting]);
    }

    public function update(MeetingRequest $request, Meeting $meeting)
    {
        $data = $request->validated();
        $attendeeIds = $data['attendees'] ?? [];
        $actionItemsInput = $data['action_items'] ?? [];
        $syncToTracker = $request->boolean('sync_to_tracker');
        unset($data['attendees'], $data['action_items'], $data['sync_to_tracker']);

        $meeting->update($data);
        $meeting->attendees()->sync($attendeeIds);

        // Baris action item yang udah gak ada di submit (dihapus user
        // dari form) ikut dihapus. work_items.meeting_action_item_id
        // otomatis null (nullOnDelete) — task yang udah kadung di Work
        // Tracker TETAP ada, cuma lepas ikatan balik ke MoM-nya.
        $submittedIds = collect($actionItemsInput)->pluck('id')->filter()->all();
        $meeting->actionItems()->whereNotIn('id', $submittedIds)->delete();

        foreach ($actionItemsInput as $row) {
            $existing = ! empty($row['id']) ? $meeting->actionItems()->find($row['id']) : null;

            if ($existing) {
                $existing->update([
                    'task' => $row['task'],
                    'pic_all' => (bool) ($row['pic_all'] ?? false),
                    'pic_employee_id' => ($row['pic_all'] ?? false) ? null : ($row['pic_employee_id'] ?? null),
                    'due_date' => $row['due_date'] ?? null,
                ]);
            } else {
                // Baris baru yang ditambah pas edit — tetap bisa
                // di-generate ke Work Tracker kalau checkbox dicentang,
                // sama kayak baris yang dibuat pas store().
                $this->createActionItem($meeting, $row, $syncToTracker);
            }
        }

        return redirect()->route('dashboard.work.meetings.index')->with('status', 'MoM berhasil diperbarui.');
    }

    public function destroy(Meeting $meeting)
    {
        $meeting->delete();

        return back()->with('status', 'MoM berhasil dihapus.');
    }

    /**
     * Blast Summary — padanan tombol "Blast Summary" di prototype yang
     * nge-push ringkasan MoM jadi Memo ke semua karyawan. Boleh
     * dipencet berkali-kali (misal ada revisi setelah rapat lanjutan);
     * tiap blast bikin Memo BARU (bukan update Memo lama), `blasted_at`
     * selalu ke-refresh ke waktu blast TERAKHIR.
     */
    public function blast(Meeting $meeting)
    {
        $meeting->load(['attendees', 'actionItems.pic']);

        $attendeeNames = $meeting->attendees->pluck('name')->implode(', ');
        $allPersons = collect([$attendeeNames, $meeting->persons_text])->filter()->implode(', ');

        $sections = collect([
            $meeting->notes ? "Catatan:\n{$meeting->notes}" : null,
            $meeting->decisions ? "Keputusan:\n{$meeting->decisions}" : null,
        ])->filter();

        if ($meeting->actionItems->isNotEmpty()) {
            $lines = $meeting->actionItems->map(function ($item) {
                $pic = $item->pic_all ? 'ALL TEAM' : ($item->pic?->name ?? 'Belum di-assign');
                $due = $item->due_date?->translatedFormat('d M Y') ?? 'tanpa due date';

                return "- {$item->task} (PIC: {$pic}, Due: {$due})";
            })->implode("\n");

            $sections->push("Action Items:\n{$lines}");
        }

        Memo::create([
            'type' => 'mom',
            'title' => $meeting->agenda,
            'content' => $sections->isNotEmpty() ? $sections->implode("\n\n") : 'Tidak ada catatan tambahan.',
            'meeting_date' => $meeting->date,
            'attendees' => $allPersons !== '' ? $allPersons : null,
            'pinned' => false,
            'created_by' => Auth::id(),
        ]);

        $meeting->update(['blasted_at' => now()]);

        return redirect()->route('dashboard.work.meetings.index')
            ->with('status', 'Ringkasan MoM berhasil di-blast jadi Memo ke semua karyawan.');
    }

    /** Data dropdown yang dipakai bareng create() & edit(). */
    private function formData(): array
    {
        return [
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
            'employees' => User::query()->orderBy('name')->get(['id', 'name']),
        ];
    }

    /**
     * Bikin 1 MeetingActionItem dari 1 baris form, opsional langsung
     * di-generate jadi WorkItem kalau checkbox "masukkan ke Work
     * Tracker" dicentang — padanan behaviour default-ON prototype.
     */
    private function createActionItem(Meeting $meeting, array $row, bool $syncToTracker): void
    {
        $picAll = (bool) ($row['pic_all'] ?? false);

        $actionItem = $meeting->actionItems()->create([
            'task' => $row['task'],
            'pic_all' => $picAll,
            'pic_employee_id' => $picAll ? null : ($row['pic_employee_id'] ?? null),
            'due_date' => $row['due_date'] ?? null,
        ]);

        if (! $syncToTracker) {
            return;
        }

        WorkItem::create([
            'project_id' => $meeting->project_id,
            'meeting_action_item_id' => $actionItem->id,
            'section' => null,
            'item_no' => $this->nextItemNo($meeting->project_id, null),
            'title' => $actionItem->task,
            'due_date' => $actionItem->due_date,
            'pic_employee_id' => $picAll ? null : $actionItem->pic_employee_id,
            'additional_pic' => $picAll ? 'ALL TEAM' : null,
            'progress' => 'Pending',
            'priority' => 'Medium',
            'notes' => 'From MoM: ' . $meeting->agenda,
            'created_by' => Auth::id(),
        ]);
    }

    /**
     * Duplikat sengaja dari `WorkTrackerBoardController::nextItemNo()`
     * (private di sana, gak di-share lewat trait) — konsisten sama
     * pola codebase ini yang nahan logic per-controller biar gampang
     * ditelusuri, bukan oversight.
     */
    private function nextItemNo(?int $projectId, ?string $section): int
    {
        return WorkItem::query()
            ->where('project_id', $projectId)
            ->where('section', $section)
            ->max('item_no') + 1;
    }
}