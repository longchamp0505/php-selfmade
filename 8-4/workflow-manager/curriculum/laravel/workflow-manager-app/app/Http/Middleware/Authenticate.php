<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        // URLから判定
        if ($request->is('client/*')) {
            return route('login', ['type' => 'client']);
        }

        if ($request->is('admin/*')) {
            return route('login', ['type' => 'admin']);
        }

        // デフォルト（ユーザー）
        return route('login', ['type' => 'user']);
    }
}
