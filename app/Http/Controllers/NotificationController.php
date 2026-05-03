<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Halaman pengaturan notifikasi
     */
    public function settings()
    {
        $user = Auth::user();

        return view('notifications.settings', compact('user'));
    }

    /**
     * Simpan preference notifikasi
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'notify_daily_journal' => 'boolean',
            'notify_weekly_review' => 'boolean',
            'notify_trade_result' => 'boolean',
            'daily_journal_time' => 'nullable|date_format:H:i',
            'weekly_review_day' => 'nullable|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
        ]);

        Auth::user()->update([
            'notify_daily_journal' => $validated['notify_daily_journal'] ?? false,
            'notify_weekly_review' => $validated['notify_weekly_review'] ?? false,
            'notify_trade_result' => $validated['notify_trade_result'] ?? false,
            'daily_journal_time' => $validated['daily_journal_time'] ?? '20:00',
            'weekly_review_day' => $validated['weekly_review_day'] ?? 'sunday',
        ]);

        return back()->with('success', 'Pengaturan notifikasi disimpan.');
    }

    /**
     * Simpan push subscription dari browser
     */
    public function subscribe(Request $request)
    {
        // If just requesting VAPID key info (placeholder request)
        if ($request->has('placeholder')) {
            return response()->json([
                'status' => 'vapid_not_configured',
                'message' => 'VAPID keys belum di-setup. Push notification akan aktif setelah HTTPS & VAPID dikonfigurasi.',
            ]);
        }

        $validated = $request->validate([
            'endpoint' => 'required|string',
            'keys.auth' => 'required|string',
            'keys.p256dh' => 'required|string',
        ]);

        Auth::user()->pushSubscriptions()->delete();

        Auth::user()->pushSubscriptions()->create([
            'endpoint' => $validated['endpoint'],
            'auth_key' => $validated['keys']['auth'],
            'p256dh_key' => $validated['keys']['p256dh'],
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json(['status' => 'subscribed']);
    }

    /**
     * Hapus push subscription
     */
    public function unsubscribe(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => 'required|string',
        ]);

        Auth::user()->pushSubscriptions()
            ->where('endpoint', $validated['endpoint'])
            ->delete();

        return response()->json(['status' => 'unsubscribed']);
    }
}
