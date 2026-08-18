<?php

namespace App\Filament\Resources\SalaryResource\Pages;

use App\Filament\Resources\SalaryResource;
use App\Models\Employee;
use App\Models\Salary;
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

                    // 3. 从表头行的下一行开始读取数据
                    $nameIdx = $columnMap['姓名'] ?? null;
                    if ($nameIdx === null) {
                        Notification::make()
                            ->title('导入失败：表头中未找到"姓名"列')
                            ->danger()
                            ->send();
                        @unlink($absolutePath);
                        return;
                    }

                    for ($i = $headerRowIndex + 1; $i < count($allRows); $i++) {
                        $row = $allRows[$i];

                        // 提取姓名
                        $name = trim((string)($row[$nameIdx] ?? ''));
                        if (empty($name)) continue;
                        if (str_contains($name, '合计')) continue;

                        // 查找员工
                        $employee = Employee::where('name', $name)->first();
                        if (!$employee) {
                            Log::warning("工资导入跳过：找不到员工 [{$name}]");
                            $skippedCount++;
                            continue;
                        }

                        // 安全数值转换辅助函数
                        $safeFloat = function ($val) {
                            $val = trim((string)$val);
                            return is_numeric($val) ? floatval($val) : 0;
                        };

                        // 利用映射表提取数据
                        Salary::create([
                            'employee_id' => $employee->id,
                            'month' => $month,
                            'department' => isset($columnMap['部门']) ? trim((string)($row[$columnMap['部门']] ?? '')) : null,
                            'position' => isset($columnMap['岗位']) ? trim((string)($row[$columnMap['岗位']] ?? '')) : null,
                            'base_salary' => $safeFloat($row[$columnMap['基本工资']] ?? 0),
                            'position_allowance' => $safeFloat($row[$columnMap['岗位津贴']] ?? 0),
                            'overtime_pay' => $safeFloat($row[$columnMap['加班费']] ?? 0),
                            'leave_days' => $safeFloat($row[$columnMap['请假天数']] ?? 0),
                            'deducted_leave_pay' => $safeFloat($row[$columnMap['扣请假工资']] ?? 0),
                            'payable_salary' => $safeFloat($row[$columnMap['应付工资']] ?? 0),
                            'social_security' => $safeFloat($row[$columnMap['社保费']] ?? 0),
                            'income_tax' => $safeFloat($row[$columnMap['个人所得税']] ?? 0),
                            'net_salary' => $safeFloat($row[$columnMap['实收工资']] ?? 0),
                        ]);
                        $importedCount++;
                    }

                    @unlink($absolutePath);

                    $message = "导入完成，成功 {$importedCount} 条记录。";
                    if ($skippedCount > 0) {
                        $message .= " 跳过 {$skippedCount} 条(员工库未找到)。";
                    }

                    Notification::make()
                        ->title($message)
                        ->success()
                        ->send();
                }),
        ];
    }
}
