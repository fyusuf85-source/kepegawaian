<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function send(
        User $user,
        string $type,
        string $title,
        string $message,
        array $data = []
    ): ?Notification {
        if (! $user->is_active) {
            return null;
        }

        if (isset($data['dedupe_key'])) {
            $exists = Notification::query()
                ->where('user_id', $user->id)
                ->where('type', $type)
                ->where('data->dedupe_key', $data['dedupe_key'])
                ->exists();

            if ($exists) {
                return null;
            }
        }

        return Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data ?: null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function sendToRole(string $roleCode, string $type, string $title, string $message, array $data = []): int
    {
        return $this->sendToUsers($this->usersWithRole($roleCode), $type, $title, $message, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function sendToPermission(string $permission, string $type, string $title, string $message, array $data = []): int
    {
        $users = User::query()
            ->where('is_active', true)
            ->whereHas('roles.permissions', fn ($query) => $query->where('code', $permission))
            ->get();

        return $this->sendToUsers($users, $type, $title, $message, $data);
    }

    /**
     * @param  Collection<int, User>  $users
     * @param  array<string, mixed>  $data
     */
    public function sendToUsers(Collection $users, string $type, string $title, string $message, array $data = []): int
    {
        $sent = 0;

        foreach ($users->unique('id') as $user) {
            if ($this->send($user, $type, $title, $message, $data)) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * @return Collection<int, User>
     */
    public function usersWithRole(string $roleCode): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('code', $roleCode))
            ->get();
    }
}
