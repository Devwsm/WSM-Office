<?php

namespace App\Http\Controllers;

use App\Support\Presence;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Heartbeat Monitor Login. Dipanggil dari JS (partials/presence-heartbeat)
 * hanya saat tab terlihat dan ada interaksi user, jadi tidak
 * memperpanjang sesi login kalau aplikasi sedang tidak dipakai.
 */
class PresenceHeartbeatController extends Controller
{
    public function ping(Request $request): Response
    {
        Presence::recordHeartbeat($request->user());

        return response()->noContent();
    }
}