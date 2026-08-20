<?php

namespace App\Filament\Resources\SalaryResource\Pages;

use App\Filament\Resources\SalaryResource;
use App\Models\Employee;
use App\Models\Salary;
use App\Models\SalaryImportError;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Log;

class ListSalaries extends ListRecords
{
    protected static string $resource = SalaryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('import')
                ->label('导入工资表')
                ->color('success')
                ->form([
                    TextInput::make('month')
                        ->label('工资归属月份')
                        ->default('2026-06')
                        ->required(),
                    FileUpload::make('file')
                        ->label('上传 Excel 文件')
                        ->disk('local')
                        ->directory('imports')
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                        ->required(),
                ])
                ->action(function (array $data) {
                    $absolutePath = storage_path('app/' . $data['file']);
                    $month = $data['month'];

                    // 仅读取第一个 Sheet
                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($absolutePath);
                    $worksheet = $spreadsheet->getSheet(0);
                    $allRows = $worksheet->toArray(null, true, true, false);

                    $importedCount = 0;
                    $skippedCount = 0;

                    // 1. 动态定位表头行：找到包含"姓名"的那一行
                    $headerRowIndex = null;
                    $columnMap = [];

                    foreach ($allRows as $index => $row) {
                        foreach ($row as $cellValue) {
                            if (trim((string)$cellValue) === '姓名') {
                                $headerRowIndex = $index;
                                break 2;
                            }
                        }
                    }

                    if ($headerRowIndex === null) {
                        Notification::make()
                            ->title('导入失败：未找到表头行（缺少"姓名"列）')
                            ->danger()
                            ->send();
                        @unlink($absolutePath);
                        return;
                    }

                    // 2. 构建列索引映射表
                    $headerRow = $allRows[$headerRowIndex];
                    $targetColumns = [
                        '姓名', '部门', '岗位',
                        '基本工资', '岗位津贴', '加班费', '请假天数', '扣请假工资',
                        '应付工资', '社保费', '个人所得税', '实收工资',
                    ];

                    foreach ($headerRow as $colIndex => $cellValue) {
                        $cellText = trim((string)$cellValue);
                        if (in_array($cellText, $targetColumns, true)) {
                            $columnMap[$cellText] = $colIndex;
                        }
                    }

                    // 3. 必需列校验（事前拦截）
                    $requiredColumns = ['姓名', '应付工资', '实收工资'];
                    $missingColumns = array_filter($requiredColumns, function ($col) use ($columnMap) {
                        return !isset($columnMap[$col]);
                    });

                    if (!empty($missingColumns)) {
                        Notification::make()
                            ->title('导入失败！模板缺少必要的列：' . implode('、', $missingColumns))
                            ->danger()
                            ->send();
                        @unlink($absolutePath);
                        return;
                    }

                    // 4. 安全取值闭包（封装在循环外，避免重复创建）
                    $safeFloat = function ($val) {
                        $val = trim((string)$val);
                        return is_numeric($val) ? floatval($val) : 0;
                    };

                    $getFloatVal = function ($columnName, $row) use ($columnMap, $safeFloat) {
                        if (!isset($columnMap[$columnName])) return 0.00;
                        return $safeFloat($row[$columnMap[$columnName]] ?? 0);
                    };

                    $getStringVal = function ($columnName, $row) use ($columnMap) {
                        if (!isset($columnMap[$columnName])) return null;
                        $idx = $columnMap[$columnName];
                        return isset($row[$idx]) ? trim((string)$row[$idx]) : null;
                    };

                    // 5. 从表头行的下一行开始读取数据
                    for ($i = $headerRowIndex + 1; $i < count($allRows); $i++) {
                        $row = $allRows[$i];

                        // 提取姓名
                        $name = trim((string)($row[$columnMap['姓名']] ?? ''));
                        if (empty($name)) continue;
                        if (str_contains($name, '合计')) continue;

                        // 查找员工
                        $employee = Employee::where('name', $name)->first();
                        if (!$employee) {
                            // 收集该行的所有原始数据
                            $rowData = [];
                            foreach ($targetColumns as $colName) {
                                if (isset($columnMap[$colName])) {
                                    $rowData[$colName] = trim((string)($row[$columnMap[$colName]] ?? ''));
                                }
                            }

                            // 写入导入失败记录池
                            SalaryImportError::create([
                                'month' => $month,
                                'name' => $name,
                                'department' => $getStringVal('部门', $row),
                                'row_data' => $rowData,
                                'error_reason' => '系统内未找到该员工姓名',
                            ]);

                            Log::warning("工资导入跳过：找不到员工 [{$name}]，已记录到失败池");
                            $skippedCount++;
                            continue;
                        }

                        // 利用安全闭包提取数据
                        Salary::create([
                            'employee_id' => $employee->id,
                            'month' => $month,
                            'department' => $getStringVal('部门', $row),
                            'position' => $getStringVal('岗位', $row),
                            'base_salary' => $getFloatVal('基本工资', $row),
                            'position_allowance' => $getFloatVal('岗位津贴', $row),
                            'overtime_pay' => $getFloatVal('加班费', $row),
                            'leave_days' => $getFloatVal('请假天数', $row),
                            'deducted_leave_pay' => $getFloatVal('扣请假工资', $row),
                            'payable_salary' => $getFloatVal('应付工资', $row),
                            'social_security' => $getFloatVal('社保费', $row),
                            'income_tax' => $getFloatVal('个人所得税', $row),
                            'net_salary' => $getFloatVal('实收工资', $row),
                        ]);
                        $importedCount++;
                    }

                    @unlink($absolutePath);

                    $message = "导入完成，成功 {$importedCount} 条记录。";
                    if ($skippedCount > 0) {
                        $message .= " 跳过 {$skippedCount} 条(员工库未找到，已记录至[导入失败记录]池)。";
                    }

                    Notification::make()
                        ->title($message)
                        ->success()
                        ->send();
                }),
        ];
    }
}
