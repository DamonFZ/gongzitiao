<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Employee;

class WeChatProxyAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::guard('employees')->check()) {
            return $next($request);
        }

        $gatewayUrl = config('wechat.gateway', 'http://oauth.damon.com');

        if (!$request->has('ticket')) {
            $currentUrl = $request->fullUrl();
            $redirectUrl = $gatewayUrl . '/auth/redirect?target_url=' . urlencode($currentUrl);

            return redirect($redirectUrl);
        }

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

                abort(500, 'Ticket 验证请求失败，请检查 Docker 内部网络或网关 API 状态。详细错误见日志。');
            }

            $data = $response->json();

            if (!isset($data['data']['openid'])) {
                Log::error('WeChatProxyAuth: 响应格式异常', [
                    'ticket' => $ticket,
                    'response' => $data,
                ]);

                abort(500, '网关返回数据格式异常，缺少 openid 字段。详细错误见日志。');
            }

            $openid = $data['data']['openid'];

            $employee = Employee::where('openid', $openid)->first();

            if (!$employee) {
                Log::info('WeChatProxyAuth: 未绑定员工，跳转绑定页', [
                    'openid' => $openid,
                ]);

                session(['temp_openid' => $openid]);

                return redirect()->route('h5.bind');
            }

            Auth::guard('employees')->login($employee);
            $request->session()->save();

            $cleanUrl = $request->url();
            $queryParams = $request->except('ticket');

            if (!empty($queryParams)) {
                $cleanUrl .= '?' . http_build_query($queryParams);
            }

            return redirect($cleanUrl);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('WeChatProxyAuth: 无法连接网关 (DNS/网络问题)', [
                'verify_url' => $verifyUrl,
                'error' => $e->getMessage(),
            ]);

            abort(500, '无法连接 OAuth 网关，请检查 Docker 内部网络或 hosts 配置。详细错误见日志。');

        } catch (\Exception $e) {
            Log::error('WeChatProxyAuth: 验证过程异常', [
                'ticket' => $ticket,
                'verify_url' => $verifyUrl,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            abort(500, 'Ticket 验证过程发生异常。详细错误见日志。');
        }
    }
}
