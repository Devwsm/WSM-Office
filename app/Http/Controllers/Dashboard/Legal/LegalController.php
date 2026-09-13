<?php

namespace App\Http\Controllers\Dashboard\Legal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\Legal\LegalDocumentRequest;
use App\Models\LegalDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * LegalController (Dashboard > Legal)
 * ---------------------------------------------------------------------
 * Fase 14 — CRUD LegalDocument (Album Contracts & Royalty Agreements
 * dalam 1 tabel, dibedain lewat kolom `category`). Prototype v18 punya
 * 2 halaman terpisah buat 2 kategori ini, tapi di sini disatuin jadi 1
 * index dengan filter kategori — sama pola persis RoyaltyController
 * (Fase 13) yang filter per `status`. File upload beneran ke
 * `Storage::disk('public')`, sama pola persis ContractController
 * (Fase 11) — bukan blob base64 kayak prototype.
 *
 * Gate 'view'/'manage' modul 'legal' — sama pola persis modul lain.
 * ---------------------------------------------------------------------
 */
class LegalController extends Controller
{
    public function index(Request $request)
    {
        $selectedCategory = $request->string('category')->toString() ?: null;

        $documents = LegalDocument::query()
            ->with('creator')
            ->when($selectedCategory, fn($q) => $q->where('category', $selectedCategory))
            ->orderByDesc('end_date')
            ->paginate(15)
            ->withQueryString();

        return view('dashboard.legal.index', [
            'documents' => $documents,
            'selectedCategory' => $selectedCategory,
        ]);
    }

    public function create()
    {
        return view('dashboard.legal.create');
    }

    public function store(LegalDocumentRequest $request)
    {
        $data = $request->validated();
        $file = $request->file('file');

        $data['file_path'] = $file->store('legal/' . $data['category'], 'public');
        $data['original_filename'] = $file->getClientOriginalName();
        $data['mime_type'] = $file->getClientMimeType();
        $data['size_bytes'] = $file->getSize();
        $data['created_by'] = Auth::id();
        unset($data['file']);

        LegalDocument::create($data);

        return redirect()->route('dashboard.legal.index')->with('status', 'Dokumen legal berhasil diupload.');
    }

    public function edit(LegalDocument $legal)
    {
        return view('dashboard.legal.edit', ['document' => $legal]);
    }

    public function update(LegalDocumentRequest $request, LegalDocument $legal)
    {
        $data = $request->validated();

        if ($request->hasFile('file')) {
            // File lama dihapus dulu — sama pola persis
            // ContractController::update(), revisi lama gak perlu
            // ditelusuri balik lewat sistem ini.
            Storage::disk('public')->delete($legal->file_path);

            $file = $request->file('file');
            $data['file_path'] = $file->store('legal/' . $data['category'], 'public');
            $data['original_filename'] = $file->getClientOriginalName();
            $data['mime_type'] = $file->getClientMimeType();
            $data['size_bytes'] = $file->getSize();
        }
        unset($data['file']);

        $legal->update($data);

        return redirect()->route('dashboard.legal.index')->with('status', 'Dokumen legal berhasil diperbarui.');
    }

    public function destroy(LegalDocument $legal)
    {
        Storage::disk('public')->delete($legal->file_path);
        $legal->delete();

        return back()->with('status', 'Dokumen legal berhasil dihapus.');
    }
}