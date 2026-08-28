<?php

use App\Http\Controllers\Api\SalaryController;
use App\Http\Controllers\Api\WechatController;
use Illuminate\Support\Facades\Route;

// 绑定页面（无需鉴权，直接显示表单）
Route::get('/h5/bind', [WechatController::class, 'showBindForm'])->name('h5.bind');
Route::post('/h5/bind', [WechatController::class, 'bind']);

// 网关 OAuth 回调处理（接收 ticket 并完成绑定）
Route::get('/h5/oauth-callback', [WechatController::class, 'handleOAuthCallback'])->name('h5.oauth.callback');

// 解绑（需要登录）
Route::middleware('auth:employees')->group(function () {
    Route::post('/h5/unbind', [WechatController::class, 'unbind'])->name('h5.unbind');
});

// H5 根路由，重定向到工资列表（使用网关 OAuth 中间件）
Route::middleware('wechat.proxy.auth')->group(function () {
    Route::get('/h5', fn () => redirect()->route('h5.salaries'));
    Route::get('/h5/salaries', [SalaryController::class, 'index'])->name('h5.salaries');
    Route::get('/h5/salary/{month}', [SalaryController::class, 'show'])->name('h5.salary.detail');
    Route::post('/h5/salary/{id}/sign', [SalaryController::class, 'sign'])->name('h5.salary.sign');
});

Route::middleware(['wechat.proxy.auth'])->group(function () {
    Route::get('/auth-test', function () {
        return '微信授权登录成功！当前用户 OpenID: ' . auth('employees')->user()->openid;
    });
});
