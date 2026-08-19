<?php

use App\Http\Controllers\Api\SalaryController;
use App\Http\Controllers\Api\WechatController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// 微信授权（无需鉴权）
Route::get('/wechat/oauth', [WechatController::class, 'oauth']);
Route::get('/wechat/callback', [WechatController::class, 'callback']);

// 绑定员工（无需鉴权）
Route::post('/h5/bind', [WechatController::class, 'bind']);

// 工资查询（需要 Sanctum 鉴权）
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/h5/salaries', [SalaryController::class, 'index']);
    Route::get('/h5/salary/{month}', [SalaryController::class, 'show']);
});
