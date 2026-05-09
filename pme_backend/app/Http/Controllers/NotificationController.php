<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->limit(40)
            ->get()
            ->map(fn ($notification) => $this->shape($notification));

        return response()->json($notifications);
    }

    public function summary(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'latest' => $user->notifications()->latest()->limit(5)->get()->map(fn ($notification) => $this->shape($notification)),
        ]);
    }

    public function markRead(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->whereKey($id)->firstOrFail();
        $notification->markAsRead();

        return response()->json($this->shape($notification->fresh()));
    }

    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'Notifications marked as read.']);
    }

    private function shape($notification): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'data' => $notification->data,
            'read_at' => $notification->read_at,
            'created_at' => $notification->created_at,
        ];
    }
}
