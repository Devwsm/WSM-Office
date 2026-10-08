<?php

namespace App\Http\Controllers\Dashboard\It;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Presence;
use Illuminate\Http\Request;

/**
 * PresenceController (Dashboard > IT > Monitor Login)
 * ---------------------------------------------------------------------
 * 2026-10-08 — daftar semua karyawan dengan status Online / Idle /
 * Offline, halaman yang sedang/terakhir dibuka, aktivitas terakhir, dan
 * login terakhir. READ-ONLY, gate `module:it,view` (Owner otomatis boleh).
 *
 * Datanya dicatat oleh TrackPresence (middleware), heartbeat JS, dan
 * listener Login/Logout di AppServiceProvider. Yang tampil hanya label
 * halaman, bukan URL atau isi apa pun yang dikerjakan karyawan.
 *
 * Halaman memuat ulang daftarnya sendiri tiap beberapa detik lewat
 * request AJAX ke URL yang sama (dijawab partial `_presence-list`).
 * ---------------------------------------------------------------------
 */
class PresenceController extends Controller
{
    public function index(Request $request)
    {
        $search = trim($request->string('q')->toString()) ?: null;
        $filter = in_array($request->query('status'), [Presence::ONLINE, Presence::IDLE, Presence::OFFLINE], true)
            ? $request->query('status')
            : null;

        $rows = User::query()
            ->orderBy('name')
            ->get()
            ->map(fn(User $u) => ['user' => $u, 'status' => Presence::status($u)]);

        $counts = [
            'total' => $rows->count(),
            Presence::ONLINE => $rows->where('status', Presence::ONLINE)->count(),
            Presence::IDLE => $rows->where('status', Presence::IDLE)->count(),
            Presence::OFFLINE => $rows->where('status', Presence::OFFLINE)->count(),
        ];

        $order = [Presence::ONLINE => 0, Presence::IDLE => 1, Presence::OFFLINE => 2];

        $rows = $rows
            ->when($filter, fn($c) => $c->where('status', $filter))
            ->when($search, function ($c) use ($search) {
                $needle = mb_strtolower($search);

                return $c->filter(fn($row) => str_contains(
                    mb_strtolower(implode(' ', [$row['user']->name, $row['user']->job_title, $row['user']->division])),
                    $needle,
                ));
            })
            ->sort(function ($a, $b) use ($order) {
                if ($a['status'] !== $b['status']) {
                    return $order[$a['status']] <=> $order[$b['status']];
                }

                // Dalam status yang sama: aktivitas terbaru dulu, yang belum pernah di akhir.
                return ($b['user']->last_seen_at?->timestamp ?? 0) <=> ($a['user']->last_seen_at?->timestamp ?? 0);
            })
            ->values();

        $data = [
            'rows' => $rows,
            'counts' => $counts,
            'search' => $search,
            'filter' => $filter,
        ];

        if ($request->ajax()) {
            return view('dashboard.it._presence-list', $data);
        }

        return view('dashboard.it.presence', $data + [
            'refreshSeconds' => 15,
        ]);
    }
}