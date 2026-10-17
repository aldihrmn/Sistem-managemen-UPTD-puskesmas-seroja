<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use App\Notifications\AppNotification;

class Notifier
{
    public static function toEmployee(?Employee $e, string $title, string $message, ?string $url = null): void
    {
        $e?->user?->notify(new AppNotification($title, $message, $url));
    }

    public static function toUsers(iterable $users, string $title, string $message, ?string $url = null): void
    {
        foreach ($users as $u) {
            $u->notify(new AppNotification($title, $message, $url));
        }
    }

    public static function toAdmins(string $title, string $message, ?string $url = null): void
    {
        self::toUsers(User::where('role', 'admin')->where('is_active', true)->get(), $title, $message, $url);
    }
}
