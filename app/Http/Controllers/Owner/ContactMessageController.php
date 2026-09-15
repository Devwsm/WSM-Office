<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;

/**
 * ContactMessageController (Owner)
 * ---------------------------------------------------------------------
 * Fase 1 (susulan, 2026-09-13) — halaman baca pesan dari form Kontak
 * publik. Gate `role:owner` (sama grup route `owner.*` lainnya) —
 * SENGAJA belum permission-based lewat `dashboard_access`, ini cuma
 * pintu pertama (Owner lihat & tandai baca), belum ada keputusan mau
 * didelegasikan ke modul mana kalau nanti perlu staf lain yang pegang.
 * ---------------------------------------------------------------------
 */
class ContactMessageController extends Controller
{
    public function index()
    {
        $messages = ContactMessage::query()->latest()->paginate(20);

        return view('owner.contact-messages.index', [
            'messages' => $messages,
        ]);
    }

    public function markRead(ContactMessage $contactMessage)
    {
        $contactMessage->markRead();

        return back()->with('status', 'Pesan dari ' . $contactMessage->name . ' ditandai sudah dibaca.');
    }
}