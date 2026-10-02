<?php

namespace App\Support;

use App\Models\User;

class ChatbotAccess
{
    public static function allowed(?User $user): bool
    {
        return config('chatbot.enabled') && $user?->hasRole('admin')
            && $user->can('access_admin_panel');
    }
}
