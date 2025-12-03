<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    /**
     * Get all notifications for authenticated user
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $notifications = DB::table('notifications')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'data' => json_decode($notification->data, true),
                    'read' => (bool) $notification->read,
                    'created_at' => $notification->created_at,
                ];
            });

        return response()->json($notifications);
    }

    /**
     * Get unread notifications count
     */
    public function unreadCount()
    {
        $user = Auth::user();
        
        $count = DB::table('notifications')
            ->where('user_id', $user->id)
            ->where('read', false)
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($id)
    {
        $user = Auth::user();
        
        $updated = DB::table('notifications')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->update([
                'read' => true,
                'updated_at' => now(),
            ]);

        if (!$updated) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        return response()->json(['message' => 'Notification marked as read']);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        
        DB::table('notifications')
            ->where('user_id', $user->id)
            ->where('read', false)
            ->update([
                'read' => true,
                'updated_at' => now(),
            ]);

        return response()->json(['message' => 'All notifications marked as read']);
    }

    /**
     * Delete a notification
     */
    public function destroy($id)
    {
        $user = Auth::user();
        
        $deleted = DB::table('notifications')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->delete();

        if (!$deleted) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        return response()->json(['message' => 'Notification deleted']);
    }

    /**
     * Create a notification (internal use or admin)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|string|in:info,success,warning,error',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'data' => 'nullable|array',
        ]);

        $id = DB::table('notifications')->insertGetId([
            'user_id' => $validated['user_id'],
            'type' => $validated['type'],
            'title' => $validated['title'],
            'message' => $validated['message'],
            'data' => json_encode($validated['data'] ?? []),
            'read' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $notification = DB::table('notifications')->where('id', $id)->first();

        return response()->json([
            'id' => $notification->id,
            'type' => $notification->type,
            'title' => $notification->title,
            'message' => $notification->message,
            'data' => json_decode($notification->data, true),
            'read' => (bool) $notification->read,
            'created_at' => $notification->created_at,
        ], 201);
    }
}
