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

        return $oauth->redirect();
    }

    /**
     * 微信授权回调
     */
    public function callback(Request $request)
    {
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
            $user = $oauth->user();
            $openid = $user->getId();
        } catch (\Exception $e) {
            Log::error('微信授权失败: ' . $e->getMessage());
            return redirect()->route('h5.bind')->withErrors(['msg' => '微信授权失败']);
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
    public function showBindForm()
    {
        return view('h5.bind');
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
