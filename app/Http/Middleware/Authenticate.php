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

        // H5 路由未登录时，重定向到微信 OAuth 授权入口
        if ($request->is('h5*')) {
            return url('/wechat/oauth');
        }

        return route('login');
    }
}
