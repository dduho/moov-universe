<?php

namespace App\Http\Controllers;

use App\Models\User;
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
        try {
            $user = Auth::user();
            
            $notifications = DB::table('notifications')
                ->where('notifiable_type', 'App\\Models\\User')
                ->where('notifiable_id', $user->id)
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
                        'is_read' => !is_null($notification->read_at),
                        'read_at' => $notification->read_at,
                        'created_at' => $notification->created_at,
                    ];
                });

            return response()->json($notifications);
        } catch (\Exception $e) {
            \Log::warning('Error getting notifications: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    /**
     * Get unread notifications count
     */
    public function unreadCount()
    {
        try {
            $user = Auth::user();
            
            $count = DB::table('notifications')
                ->where('notifiable_type', 'App\\Models\\User')
                ->where('notifiable_id', $user->id)
                ->whereNull('read_at')
                ->count();

            return response()->json(['count' => $count]);
        } catch (\Exception $e) {
            // Si la table n'existe pas encore, retourner 0
            \Log::warning('Error getting notification count: ' . $e->getMessage());
            return response()->json(['count' => 0]);
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($id)
    {
        try {
            $user = Auth::user();
            
            $updated = DB::table('notifications')
                ->where('id', $id)
                ->where('notifiable_type', 'App\\Models\\User')
                ->where('notifiable_id', $user->id)
                ->update([
                    'read_at' => now(),
                    'updated_at' => now(),
                ]);

            if (!$updated) {
                return response()->json(['message' => 'Notification not found'], 404);
            }

            return response()->json(['message' => 'Notification marked as read']);
        } catch (\Exception $e) {
            \Log::warning('Error marking notification as read: ' . $e->getMessage());
            return response()->json(['message' => 'Error marking notification as read'], 200);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        try {
            $user = Auth::user();
            
            DB::table('notifications')
                ->where('notifiable_type', 'App\\Models\\User')
                ->where('notifiable_id', $user->id)
                ->whereNull('read_at')
                ->update([
                    'read_at' => now(),
                    'updated_at' => now(),
                ]);

            return response()->json(['message' => 'All notifications marked as read']);
        } catch (\Exception $e) {
            \Log::warning('Error marking all notifications as read: ' . $e->getMessage());
            return response()->json(['message' => 'Error marking notifications as read'], 200);
        }
    }

    /**
     * Delete a notification
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();
            
            $deleted = DB::table('notifications')
                ->where('id', $id)
                ->where('notifiable_type', 'App\\Models\\User')
                ->where('notifiable_id', $user->id)
                ->delete();

            if (!$deleted) {
                return response()->json(['message' => 'Notification not found'], 404);
            }

            return response()->json(['message' => 'Notification deleted']);
        } catch (\Exception $e) {
            \Log::warning('Error deleting notification: ' . $e->getMessage());
            return response()->json(['message' => 'Error deleting notification'], 200);
        }
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
