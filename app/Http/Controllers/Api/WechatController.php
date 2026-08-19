<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use EasyWeChat\OfficialAccount\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WechatController extends Controller
{
    /**
     * 重定向到微信静默授权页
     */
    public function oauth(Request $request)
    {
        $appId = config('wechat.official_account.app_id');
        $secret = config('wechat.official_account.secret');

        if (!$appId || !$secret) {
            return '微信公众号配置缺失，请在 .env 中配置 WECHAT_OFFICIAL_ACCOUNT_APP_ID 和 WECHAT_OFFICIAL_ACCOUNT_SECRET';
        }

        $config = [
            'app_id' => $appId,
            'secret' => $secret,
            'oauth' => [
                'scopes' => ['snsapi_base'],
                'callback' => url('/wechat/callback'),
            ],
        ];

        $app = new Application($config);
        $oauth = $app->getOAuth();

        // EasyWeChat v6 的 redirect() 返回 URL 字符串，需要用 redirect()->away() 包装
        $url = $oauth->redirect();

        return redirect()->away($url);
    }

    /**
     * 微信授权回调
     */
    public function callback(Request $request)
    {
        $code = $request->input('code');

        if (!$code) {
            return redirect()->route('h5.bind')->withErrors(['msg' => '微信授权失败，缺少授权码']);
        }

        $config = [
            'app_id' => config('wechat.official_account.app_id'),
            'secret' => config('wechat.official_account.secret'),
            'oauth' => [
                'scopes' => ['snsapi_base'],
                'callback' => url('/wechat/callback'),
            ],
        ];

        $app = new Application($config);
        $oauth = $app->getOAuth();

        try {
            // EasyWeChat v6 使用 userFromCode() 获取用户
            $user = $oauth->userFromCode($code);
            $openid = $user->getId();
        } catch (\Exception $e) {
            return redirect()->route('h5.bind')->withErrors(['msg' => '微信授权失败：' . $e->getMessage()]);
        }

        // 查找已绑定该 openid 的员工
        $employee = Employee::where('openid', $openid)->first();

        if ($employee) {
            Auth::guard('employees')->login($employee);
            return redirect()->route('h5.salaries');
        }

        // 未绑定，存入 session 后跳转绑定页
        session(['temp_openid' => $openid]);

        return redirect()->route('h5.bind');
    }

    /**
     * 显示绑定表单页面
     */
    public function showBindForm(Request $request)
    {
        $employee = Auth::guard('employees')->user();

        // 场景 1：如果用户已登录，且数据库中已经有 openid，说明已经绑定过了，直接返回视图（展示解绑按钮）
        if ($employee && !empty($employee->openid)) {
            return view('h5.bind', compact('employee'));
        }

        // 场景 2：用户未绑定（无论是否已登录）。此时必须确保 Session 中有从微信回调拿到的 temp_openid。
        // 如果没有 temp_openid，强制重定向到微信授权路由去获取！
        if (!session()->has('temp_openid')) {
            return redirect()->route('wechat.oauth');
        }

        // 场景 3：未绑定，且 Session 中已有 temp_openid，正常展示绑定表单
        return view('h5.bind', compact('employee'));
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

    /**
     * 处理绑定提交
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

        // 如果有临时 openid（来自微信授权），则更新
        $tempOpenid = session('temp_openid');
        if ($tempOpenid) {
            $employee->update(['openid' => $tempOpenid]);
            session()->forget('temp_openid');
        }

        Auth::guard('employees')->login($employee);

        return redirect()->route('h5.salaries');
    }
}
