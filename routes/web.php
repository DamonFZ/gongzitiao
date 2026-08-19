<?php

use App\Http\Controllers\Api\SalaryController;
use App\Http\Controllers\Api\WechatController;
use Illuminate\Support\Facades\Route;

// 微信授权（无需鉴权）
Route::get('/wechat/oauth', [WechatController::class, 'oauth']);
Route::get('/wechat/callback', [WechatController::class, 'callback']);

// 绑定页面（无需鉴权）
Route::get('/h5/bind', [WechatController::class, 'showBindForm'])->name('h5.bind');
Route::post('/h5/bind', [WechatController::class, 'bind']);

// 解绑（需要登录）
Route::middleware('auth:employees')->group(function () {
    Route::post('/h5/unbind', [WechatController::class, 'unbind'])->name('h5.unbind');
});

// H5 根路由，重定向到工资列表（配合鉴权中间件，未登录自动触发 OAuth）
Route::middleware('auth:employees')->group(function () {
    Route::get('/h5', fn () => redirect()->route('h5.salaries'));
    Route::get('/h5/salaries', [SalaryController::class, 'index'])->name('h5.salaries');
    Route::get('/h5/salary/{month}', [SalaryController::class, 'show'])->name('h5.salary.detail');
});
