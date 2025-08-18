<?php

namespace App\Helpers;

use App\Models\AuditTrails;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuditLogger
{
    public static function log($title, $description = null, $status = 'success', $userId = null, $category = null, $ip = null)
    {
        $clientIp = $ip ?? (request()?->ip() ?? null);

        AuditTrails::create([
            'id' => Str::uuid(),
            'title' => $title,
            'description' => $description,
            'status' => $status,
            'user_id' => $userId ?? (Auth::check() ? Auth::id() : null),
            'category' => $category,
            'ip' => $clientIp
        ]);
    }
}
