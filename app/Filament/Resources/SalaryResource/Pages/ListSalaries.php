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
                    
                    // 使用 PhpSpreadsheet 原生读取，完美解决多 Sheet 动态名称问题
                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($absolutePath);
                    $sheetNames = $spreadsheet->getSheetNames();
                    
                    $importedCount = 0;
                    $skippedCount = 0;

                    foreach ($sheetNames as $sheetName) {
                        if (trim($sheetName) === '合计') continue; // 跳过合计表

                        $worksheet = $spreadsheet->getSheetByName($sheetName);
                        $rows = $worksheet->toArray();

                        foreach ($rows as $index => $row) {
                            if ($index === 0) continue; // 跳过第一行表头

                            $name = trim($row[0] ?? '');
                            if (empty($name)) continue;

                            // 查找员工，匹配不到则跳过并记录日志
                            $employee = Employee::where('name', $name)->first();
                            if (!$employee) {
                                Log::warning("工资导入跳过：找不到员工 [{$name}]");
                                $skippedCount++;
                                continue;
                            }

                            // 根据固定列索引组装数据
                            Salary::create([
                                'employee_id' => $employee->id,
                                'month' => $month,
                                'department' => $row[1] ?? null,
                                'position' => $row[2] ?? null,
                                'base_salary' => is_numeric($row[6]) ? $row[6] : 0,
                                'position_allowance' => is_numeric($row[7]) ? $row[7] : 0,
                                'overtime_pay' => is_numeric($row[8]) ? $row[8] : 0,
                                'leave_days' => is_numeric($row[9]) ? $row[9] : 0,
                                'deducted_leave_pay' => is_numeric($row[10]) ? $row[10] : 0,
                                'payable_salary' => is_numeric($row[11]) ? $row[11] : 0,
                                'social_security' => is_numeric($row[12]) ? $row[12] : 0,
                                'income_tax' => is_numeric($row[13]) ? $row[13] : 0,
                                'net_salary' => is_numeric($row[14]) ? $row[14] : 0,
                            ]);
                            $importedCount++;
                        }
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
