<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WechatController extends Controller
{
    /**
     * 显示绑定表单页面（直接显示，不跳转）
     */
    public function showBindForm(Request $request)
    {
        $employee = Auth::guard('employees')->user();

        // 已绑定，直接显示
        if ($employee && !empty($employee->openid)) {
            return view('h5.bind', compact('employee'));
        }

        // 未绑定，显示表单（可能已有 temp_openid 来自网关回调）
        return view('h5.bind', compact('employee'));
    }

    /**
     * 处理绑定提交：验证身份 → 发起网关 OAuth
     */
    public function bind(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'phone' => 'required|string',
            'id_card' => 'required|string',
        ]);

        $employee = Employee::where('name', $request->name)
            ->where('phone', $request->phone)
            ->where('id_card', $request->id_card)
            ->first();

        if (!$employee) {
            return back()->withErrors(['msg' => '身份信息不匹配，请核对姓名、手机号和身份证号']);
        }

        // 互斥锁：如果该员工已经被绑定过了，直接拦截
        if (!empty($employee->openid)) {
            return back()->withInput()->withErrors([
                'msg' => '安全提示：该员工信息已被其他微信号绑定！为了保护您的隐私，如需换绑，请联系管理员在后台解除原有绑定。'
            ]);
        }

        // 身份验证通过，将员工 ID 存入 session，然后重定向到网关 OAuth
        session(['pending_bind_employee_id' => $employee->id]);

        $gatewayUrl = config('wechat.gateway', 'http://oauth.damon.com');
        $callbackUrl = route('h5.oauth.callback');
        $targetUrl = $gatewayUrl . '/auth/redirect?target_url=' . urlencode($callbackUrl);

        return redirect($targetUrl);
    }

    /**
     * 处理网关 OAuth 回调：验证 ticket → 获取 openid → 绑定员工 → 登录
     */
    public function handleOAuthCallback(Request $request)
    {
        $ticket = $request->input('ticket');

        if (!$ticket) {
            return redirect()->route('h5.bind')->withErrors(['msg' => '授权失败，缺少 ticket 参数']);
        }

        $gatewayUrl = config('wechat.gateway', 'http://oauth.damon.com');
        $verifyUrl = $gatewayUrl . '/api/auth/verify?ticket=' . $ticket;

        Log::info('WechatController: 开始验证 Ticket', ['verify_url' => $verifyUrl]);

        try {
            $response = Http::timeout(10)->withOptions(['verify' => false])->get($verifyUrl);

            if (!$response->successful()) {
                Log::error('WechatController: Ticket 验证返回非 200', [
                    'ticket' => $ticket,
                    'status' => $response->status(),
                    'response_body' => $response->body(),
                ]);

                return redirect()->route('h5.bind')->withErrors(['msg' => '网关验证失败，请重试']);
            }

            $data = $response->json();

            if (!isset($data['data']['openid'])) {
                Log::error('WechatController: 响应格式异常', [
                    'ticket' => $ticket,
                    'response' => $data,
                ]);

                return redirect()->route('h5.bind')->withErrors(['msg' => '网关返回数据格式异常']);
            }

            $openid = $data['data']['openid'];

            // 从 session 获取待绑定的员工 ID
            $employeeId = session('pending_bind_employee_id');

            if (!$employeeId) {
                Log::warning('WechatController: session 中无 pending_bind_employee_id', [
                    'openid' => $openid,
                ]);

                return redirect()->route('h5.bind')->withErrors(['msg' => '绑定会话已过期，请重新提交']);
            }

            $employee = Employee::find($employeeId);

            if (!$employee) {
                Log::error('WechatController: 员工不存在', [
                    'employee_id' => $employeeId,
                    'openid' => $openid,
                ]);

                return redirect()->route('h5.bind')->withErrors(['msg' => '员工信息不存在']);
            }

            // 绑定 openid
            $employee->update(['openid' => $openid]);

            // 清理 session
            session()->forget('pending_bind_employee_id');

            // 登录
            Auth::guard('employees')->login($employee);
            $request->session()->save();

            Log::info('WechatController: 绑定成功', [
                'employee_id' => $employee->id,
                'openid' => $openid,
            ]);

            return redirect()->route('h5.salaries');

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('WechatController: 无法连接网关', [
                'verify_url' => $verifyUrl,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('h5.bind')->withErrors(['msg' => '无法连接 OAuth 网关，请检查网络']);

        } catch (\Exception $e) {
            Log::error('WechatController: 验证过程异常', [
                'ticket' => $ticket,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('h5.bind')->withErrors(['msg' => '授权过程发生异常，请重试']);
        }
    }

    /**
     * 解除绑定
     */
    public function unbind(Request $request)
    {
        $employee = Auth::guard('employees')->user();

        if ($employee) {
            $employee->update(['openid' => null]);
        }

        Auth::guard('employees')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('h5.bind');
    }
}
