<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    protected function redirectTo($request): ?string
    {
        if (!$request->expectsJson()) {
            return route('api.auth.login');
        }
        return null;
    }
}
