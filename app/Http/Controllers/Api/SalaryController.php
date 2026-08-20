<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Salary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SalaryController extends Controller
{
    /**
     * 工资月份列表
     */
    public function index(Request $request)
    {
        $employee = Auth::guard('employees')->user();

        $salaries = Salary::where('employee_id', $employee->id)
            ->select('month', 'status', 'signature_path')
            ->distinct()
            ->orderBy('month', 'desc')
            ->get();

        return view('h5.list', compact('salaries'));
    }

    /**
     * 指定月份工资明细
     */
    public function show(Request $request, $month)
    {
        $employee = Auth::guard('employees')->user();

        $salary = Salary::where('employee_id', $employee->id)
            ->where('month', $month)
            ->first();

        if (!$salary) {
            abort(404, '未找到该月份的工资记录');
        }

        // 状态自动流转：0 -> 1 (未读 -> 已读未签名)
        if ($salary->status === 0) {
            $salary->status = 1;
            $salary->save();
        }

        return view('h5.detail', compact('salary'));
    }

    /**
     * 保存电子签名
     */
    public function sign(Request $request, $id)
    {
        $employee = Auth::guard('employees')->user();

        $salary = Salary::where('employee_id', $employee->id)
            ->where('id', $id)
            ->first();

        if (!$salary) {
            return response()->json(['success' => false, 'message' => '未找到该工资条记录'], 404);
        }

        if ($salary->status === 2) {
            return response()->json(['success' => false, 'message' => '该工资条已签名确认，不可重复签名'], 400);
        }

        $signature = $request->input('signature');

        if (empty($signature)) {
            return response()->json(['success' => false, 'message' => '签名数据不能为空'], 422);
        }

        // 剥离 Base64 header
        if (strpos($signature, 'data:image/') === 0) {
            $signature = preg_replace('/^data:image\/\w+;base64,/', '', $signature);
        }

        // 解码 Base64 数据
        $imageData = base64_decode($signature);

        if ($imageData === false) {
            return response()->json(['success' => false, 'message' => '签名数据解码失败'], 422);
        }

        // ============ GD 图像处理：逆时针旋转 90 度 ============
        // 1. 从字符串创建图像资源
        $sourceImage = @imagecreatefromstring($imageData);

        if ($sourceImage === false) {
            return response()->json(['success' => false, 'message' => '签名图片解析失败'], 422);
        }

        // 2. 处理 PNG 透明背景（否则旋转后变黑）
        imagealphablending($sourceImage, false);
        imagesavealpha($sourceImage, true);
        $transparent = imagecolorallocatealpha($sourceImage, 255, 255, 255, 127);

        // 3. 逆时针旋转 90 度（PHP imagerotate 正数表示逆时针）
        $rotatedImage = imagerotate($sourceImage, 90, $transparent);

        if ($rotatedImage !== false) {
            imagealphablending($rotatedImage, false);
            imagesavealpha($rotatedImage, true);

            // 4. 捕获旋转后的图像数据
            ob_start();
            imagepng($rotatedImage);
            $finalImageData = ob_get_clean();

            // 5. 释放内存
            imagedestroy($rotatedImage);
        } else {
            // 旋转失败则使用原图
            $finalImageData = $imageData;
        }

        imagedestroy($sourceImage);
        // ============ GD 图像处理结束 ============

        // 生成文件名并保存
        $fileName = "signature_{$id}_" . time() . ".png";
        $savePath = "signatures/{$fileName}";

        // 保存旋转后的图像到 storage/app/public/signatures/ 目录
        Storage::disk('public')->put($savePath, $finalImageData);

        // 更新数据库
        $salary->signature_path = $savePath;
        $salary->status = 2;
        $salary->save();

        return response()->json([
            'success' => true,
            'message' => '签名保存成功',
            'signature_url' => asset('storage/' . $savePath),
        ]);
    }
}
