<?php

namespace App\Providers;

use App\Models\Memo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /**
         * Audit ronde 6 (2026-09-09) — Inbox header (ikon amplop + badge
         * unread), padanan `openEmployeeInboxV19`/`inboxUnreadCountV19`
         * di prototype. Prototype sengaja bisa diakses dari HALAMAN MANA
         * PUN (bukan cuma Home) lewat header App Mode, jadi datanya
         * dibagikan lewat View Composer ke `layouts.employee` langsung
         * (bukan lewat tiap Controller satu-satu kayak `$memos` di
         * HomeController) — modal-nya nempel di layout, bukan halaman
         * terpisah, biar konsisten kelihatan di semua tab bottom-nav.
         *
         * Query-nya sengaja mirip `$memos` punya HomeController (semua
         * memo, urut latestFirst, TANPA filter dashboard_access — Memo
         * Forum pengumuman ke semua tim) tapi ditambah filter "belum
         * disembunyikan" di level PHP (bukan query) karena
         * `isHiddenBy()`/`isReadBy()` butuh koleksi `reads` yang udah
         * di-load, sama pola kayak `readStateFor()` di model Memo.
         */
        View::composer('layouts.employee', function ($view) {
            $user = Auth::user();

            if (! $user) {
                return;
            }

            $memos = Memo::query()->latestFirst()->with(['reads' => function ($q) use ($user) {
                $q->where('user_id', $user->id);
            }, 'creator'])->get()->reject(fn(Memo $m) => $m->isHiddenBy($user))->values();

            $view->with('inboxMemos', $memos);
            $view->with('inboxUnreadCount', $memos->reject(fn(Memo $m) => $m->isReadBy($user))->count());
        });
    }
}