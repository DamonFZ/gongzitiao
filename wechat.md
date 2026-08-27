# 微信 OAuth 网关对接指南

本文档供子项目的 AI 编程助手阅读，用于指导子项目如何与统一的微信 OAuth 授权网关（`oauth.damon.com`）对接。

## 核心原则

1. **子项目严禁直接调用 EasyWeChat 或微信 OAuth API**。所有微信授权请求必须通过网关中转。
2. 网关域名从 `.env` 的 `WECHAT_OAUTH_GATEWAY` 读取，禁止硬编码。
3. 子项目只负责：接收网关回调的 ticket → 验证 ticket → 处理业务逻辑。

---

## 对接步骤

### 1. 环境变量

在子项目的 `.env` 中添加：

```env
WECHAT_OAUTH_GATEWAY=http://oauth.damon.com
```

生产环境替换为实际域名，例如：

```env
WECHAT_OAUTH_GATEWAY=https://oauth.example.com
```

### 2. 配置文件

创建或修改 `config/wechat.php`：

```php
<?php

return [
    'gateway' => env('WECHAT_OAUTH_GATEWAY', 'http://oauth.damon.com'),
];
```

### 3. 创建中间件

创建 `app/Http/Middleware/WeChatProxyAuth.php`：

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeChatProxyAuth
{
    public function handle(Request $request, Closure $next)
    {
        // 1. 已登录则放行
        if (Auth::guard('web')->check()) {
            return $next($request);
        }

        $gatewayUrl = config('wechat.gateway', 'http://oauth.damon.com');

        // 2. 无 ticket → 重定向到网关
        if (!$request->has('ticket')) {
            return redirect($gatewayUrl . '/auth/redirect?target_url=' . urlencode($request->fullUrl()));
        }

        // 3. 有 ticket → 调用网关验证 API
        $ticket = $request->input('ticket');
        $verifyUrl = $gatewayUrl . '/api/auth/verify?ticket=' . $ticket;

        Log::info('WeChatProxyAuth: 开始验证 Ticket', ['verify_url' => $verifyUrl]);

        try {
            $response = Http::timeout(10)->withOptions(['verify' => false])->get($verifyUrl);

            if (!$response->successful()) {
                Log::error('WeChatProxyAuth: Ticket 验证返回非 200', [
                    'ticket' => $ticket,
                    'verify_url' => $verifyUrl,
                    'status' => $response->status(),
                    'response_body' => $response->body(),
                ]);
                abort(500, 'Ticket 验证请求失败，请检查网关 API 状态。详细错误见日志。');
            }

            $data = $response->json();

            if (!isset($data['data']['openid'])) {
                Log::error('WeChatProxyAuth: 响应格式异常', [
                    'ticket' => $ticket,
                    'response' => $data,
                ]);
                abort(500, '网关返回数据格式异常，缺少 openid 字段。');
            }

            $openid = $data['data']['openid'];

            // 4. 根据业务需求处理 openid（查找用户、创建用户、跳转绑定页等）
            // 示例：查找或创建用户
            $user = \App\Models\User::firstOrCreate(
                ['openid' => $openid],
                ['name' => '微信用户_' . substr($openid, 0, 8)]
            );

            Auth::guard('web')->login($user);
            $request->session()->save();

            // 5. 剥离 ticket 参数，重定向到干净的 URL
            $cleanUrl = $request->url();
            $queryParams = $request->except('ticket');
            if (!empty($queryParams)) {
                $cleanUrl .= '?' . http_build_query($queryParams);
            }

            return redirect($cleanUrl);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('WeChatProxyAuth: 无法连接网关', [
                'verify_url' => $verifyUrl,
                'error' => $e->getMessage(),
            ]);
            abort(500, '无法连接 OAuth 网关，请检查网络或 hosts 配置。');

        } catch (\Exception $e) {
            Log::error('WeChatProxyAuth: 验证过程异常', [
                'ticket' => $ticket,
                'verify_url' => $verifyUrl,
                'error' => $e->getMessage(),
            ]);
            abort(500, 'Ticket 验证过程发生异常。');
        }
    }
}
```

> **注意：** 上面的 `Auth::guard('web')` 和 `\App\Models\User` 需要根据子项目的实际 guard 和模型调整。如果子项目使用 `employees` guard，则改为 `Auth::guard('employees')` 和对应的 Employee 模型。

### 4. 注册中间件

在 `app/Http/Kernel.php` 的 `$middlewareAliases` 中添加：

```php
'wechat.proxy.auth' => \App\Http\Middleware\WeChatProxyAuth::class,
```

### 5. 应用中间件到路由

在 `routes/web.php` 中，将需要微信授权的路由包裹在 `wechat.proxy.auth` 中间件下：

```php
Route::middleware('wechat.proxy.auth')->group(function () {
    Route::get('/protected-page', function () {
        return '已登录用户: ' . auth()->user()->name;
    });
});
```

> **重要：** 不要在这些路由上同时使用 `auth` 或 `auth:xxx` 中间件，否则会导致无限重定向。`wechat.proxy.auth` 已经包含了登录检查逻辑。

### 6. 修改未登录重定向逻辑

如果子项目有自定义的 `Authenticate` 中间件（`app/Http/Middleware/Authenticate.php`），需要将未登录时的重定向目标改为网关，而非子项目自己的 OAuth 路由：

```php
protected function redirectTo(Request $request): ?string
{
    if ($request->expectsJson()) {
        return null;
    }

    // 需要微信授权的路径，重定向到网关
    if (str_starts_with($request->path(), 'h5')) {
        $gatewayUrl = config('wechat.gateway', 'http://oauth.damon.com');
        return $gatewayUrl . '/auth/redirect?target_url=' . urlencode($request->fullUrl());
    }

    return route('login');
}
```

---

## 网关 API 说明

### 授权入口

```
GET {WECHAT_OAUTH_GATEWAY}/auth/redirect?target_url={子项目当前完整URL}
```

- 网关会处理微信 OAuth 流程
- 完成后回调子项目，URL 上携带 `ticket` 参数

### 验证 API

```
GET {WECHAT_OAUTH_GATEWAY}/api/auth/verify?ticket={ticket}
```

成功响应：

```json
{
    "data": {
        "openid": "oEXsXuK363lT73JXdmrIjGWCPFII"
    }
}
```

失败响应：返回非 200 状态码。

---

## 关键注意事项

### 禁止事项

- **禁止**在子项目中 `use EasyWeChat\OfficialAccount\Application` 或调用 `$app->getOAuth()`
- **禁止**在子项目中直接构建微信 authorize URL
- **禁止**在子项目的 `routes/web.php` 中定义 `/wechat/oauth` 或 `/wechat/callback` 路由（除非用于员工绑定等独立业务）
- **禁止**在 ticket 验证失败时重定向回网关（会导致无限循环），应使用 `abort(500)`

### 必须事项

- 网关域名必须从 `config('wechat.gateway')` 读取，禁止硬编码
- `Auth::login()` 后必须调用 `$request->session()->save()` 强制落盘，否则重定向后 session 丢失会导致无限循环
- 中间件中的 `Auth::guard()` 必须与路由实际使用的 guard 一致
- 清理 URL 时必须剥离 `ticket` 参数，避免浏览器地址栏泄露

### 绑定流程（如需要）

如果子项目有"用户绑定"需求（如员工绑定微信），流程为：

1. 用户访问受保护页面 → 中间件发现未登录 → 跳转网关 OAuth
2. 网关完成微信授权 → 回调子项目带 ticket
3. 中间件验证 ticket → 拿到 openid → 查库发现未绑定
4. 将 openid 存入 `session(['temp_openid' => $openid])` → 跳转绑定页
5. 用户在绑定页输入身份信息 → 后端查到员工 → 用 session 中的 `temp_openid` 完成绑定 → 登录

---

## 排查问题

日志位置：`storage/logs/laravel.log`

关键日志关键词：
- `WeChatProxyAuth: 开始验证 Ticket` — 中间件已触发，verify_url 是否正确
- `WeChatProxyAuth: Ticket 验证返回非 200` — 网关返回了错误状态码
- `WeChatProxyAuth: 无法连接网关` — DNS 或网络问题
- `WeChatProxyAuth: 响应格式异常` — 网关返回的 JSON 缺少 openid

网络连通性测试（在子项目容器内执行）：

```bash
docker exec bt_dev_env curl -s -w "\nHTTP_CODE: %{http_code}\n" "http://oauth.damon.com/api/auth/verify?ticket=test"
```
