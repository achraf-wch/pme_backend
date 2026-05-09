<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\DashboardNotification;
use Illuminate\Support\Collection;

class NotificationService
{
    private const ADMIN_ROLES = ['central_admin', 'super_admin'];

    public function notifyAudience(array $audience, array $payload, ?int $exceptUserId = null): void
    {
        $query = User::query()
            ->where('is_active', true)
            ->with('role');

        if (in_array('public', $audience, true)) {
            $query->whereHas('role');
        } else {
            $query->whereHas('role', fn ($roleQuery) => $roleQuery->whereIn('name', $audience));
        }

        if ($exceptUserId) {
            $query->whereKeyNot($exceptUserId);
        }

        $this->send($query->get(), $payload);
    }

    public function notifyAdmins(array $payload, ?int $exceptUserId = null): void
    {
        $query = User::query()
            ->where('is_active', true)
            ->whereHas('role', fn ($roleQuery) => $roleQuery->whereIn('name', self::ADMIN_ROLES));

        if ($exceptUserId) {
            $query->whereKeyNot($exceptUserId);
        }

        $this->send($query->get(), $payload);
    }

    private function send(Collection $users, array $payload): void
    {
        $payload = array_merge([
            'category' => 'system',
            'title' => 'Nouvelle notification',
            'body' => null,
            'action_url' => '/dashboard',
            'action_label' => 'Ouvrir',
            'source_type' => null,
            'source_id' => null,
        ], $payload);

        $users->unique('id')->each(fn (User $user) => $user->notify(new DashboardNotification($payload)));
    }
}
