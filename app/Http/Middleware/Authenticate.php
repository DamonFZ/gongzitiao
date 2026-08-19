<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        $path = $request->path();
        Log::debug('Authenticate middleware check', [
            'path' => $path,
            'is_h5' => $request->is('h5*'),
            'is_h5_path' => str_starts_with($path, 'h5'),
        ]);

        // H5 路由未登录时，重定向到微信 OAuth 授权入口
        if (str_starts_with($path, 'h5')) {
            Log::info('Redirecting to wechat oauth');
            return url('/wechat/oauth');
        }

        return route('login');
    }
}
