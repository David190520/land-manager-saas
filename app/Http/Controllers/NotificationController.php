<?php

namespace App\Http\Controllers;

use App\Models\InternalNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class NotificationController extends Controller
{
    /**
     * Get notifications for the current user (JSON endpoint for the bell dropdown).
     */
    public function index(Request $request)
    {
        // Trigger generation on each load (lightweight due to dedup)
        Artisan::call('notifications:generate');

        $notifications = InternalNotification::forUser($request->user())
            ->orderByRaw("CASE urgency WHEN 'high' THEN 0 WHEN 'medium' THEN 1 ELSE 2 END")
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($n) {
                return [
                    'id' => $n->id,
                    'type' => $n->type,
                    'urgency' => $n->urgency,
                    'title' => $n->title,
                    'message' => $n->message,
                    'action_url' => $n->action_url,
                    'is_read' => $n->is_read,
                    'created_at' => $n->created_at->diffForHumans(),
                    'created_at_raw' => $n->created_at->toISOString(),
                ];
            });

        $unreadCount = InternalNotification::forUser($request->user())
            ->unread()
            ->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, InternalNotification $notification)
    {
        if ($notification->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read for the current user.
     */
    public function markAllAsRead(Request $request)
    {
        InternalNotification::forUser($request->user())
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json(['success' => true]);
    }
}
