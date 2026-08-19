<?php

use App\Http\Controllers\Api\SalaryController;
use App\Http\Controllers\Api\WechatController;
use Illuminate\Support\Facades\Route;

// 微信授权（无需鉴权）
Route::get('/wechat/oauth', [WechatController::class, 'oauth']);
Route::get('/wechat/callback', [WechatController::class, 'callback']);

// 绑定页面
Route::get('/h5/bind', [WechatController::class, 'showBindForm'])->name('h5.bind');
Route::post('/h5/bind', [WechatController::class, 'bind']);

// 工资查询（需要登录）
Route::middleware('auth:employees')->group(function () {
    Route::get('/h5/salaries', [SalaryController::class, 'index'])->name('h5.salaries');
    Route::get('/h5/salary/{month}', [SalaryController::class, 'show'])->name('h5.salary.detail');
});
