<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use EasyWeChat\OfficialAccount\Application;
use Illuminate\Http\Request;
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
                'callback' => config('app.url') . '/api/wechat/callback',
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
                'callback' => config('app.url') . '/api/wechat/callback',
            ],
        ];

        $app = new Application($config);
        $oauth = $app->getOAuth();

        try {
            $user = $oauth->user();
            $openid = $user->getId();
        } catch (\Exception $e) {
            Log::error('微信授权失败: ' . $e->getMessage());
            return response()->json(['message' => '微信授权失败'], 500);
        }

        // 查找已绑定该 openid 的员工
        $employee = Employee::where('openid', $openid)->first();

        if ($employee) {
            $token = $employee->createToken('h5-token')->plainTextToken;
            return response()->json([
                'token' => $token,
                'employee' => $employee,
            ]);
        }

        // 未绑定，返回 openid 供前端引导绑定
        return response()->json([
            'message' => '未绑定员工，请先绑定',
            'openid' => $openid,
        ], 404);
    }

    /**
     * 绑定员工信息
     */
    public function bind(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'phone' => 'required|string',
            'id_card' => 'required|string',
            'openid' => 'required|string',
        ]);

        $employee = Employee::where('name', $request->name)
            ->where('phone', $request->phone)
            ->where('id_card', $request->id_card)
            ->first();

        if (!$employee) {
            return response()->json(['message' => '员工信息不匹配，请核对姓名、手机号和身份证号'], 422);
        }

        $employee->update(['openid' => $request->openid]);

        $token = $employee->createToken('h5-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'employee' => $employee,
        ]);
    }
}
