<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        // H5 路由未登录时，重定向到 OAuth 网关
        if (str_starts_with($request->path(), 'h5')) {
            $gatewayUrl = config('wechat.gateway', 'http://oauth.damon.com');
            return $gatewayUrl . '/auth/redirect?target_url=' . urlencode($request->fullUrl());
        }

        return route('login');
    }
}
