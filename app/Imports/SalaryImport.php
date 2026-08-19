<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\Salary;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeSheet;

class SalaryImport implements ToCollection, WithHeadingRow, SkipsEmptyRows, WithEvents
{
    protected $currentSheetName = '';

    /**
     * 注册事件监听器
     */
    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function (BeforeSheet $event) {
                $this->currentSheetName = $event->getSheet()->getTitle();
            },
        ];
    }

    /**
     * 处理数据
     */
    public function collection(Collection $rows)
    {
        // 跳过 '合计' Sheet
        if ($this->currentSheetName === '合计') {
            return;
        }

        foreach ($rows as $row) {
            // 跳过空行
            if (empty($row['姓名'])) {
                continue;
            }

            // 根据姓名查找员工
            $employee = Employee::where('name', $row['姓名'])->first();

            // 如果员工不存在，跳过该行
            if (!$employee) {
                continue;
            }

            // 清洗并转换数据
            $salaryData = [
                'employee_id' => $employee->id,
                'month' => $this->cleanString($row['月份'] ?? ''),
                'department' => $this->cleanString($row['部门'] ?? ''),
                'position' => $this->cleanString($row['岗位'] ?? ''),
                'base_salary' => $this->cleanFloat($row['基本工资'] ?? 0),
                'position_allowance' => $this->cleanFloat($row['岗位津贴'] ?? 0),
                'overtime_pay' => $this->cleanFloat($row['加班费'] ?? 0),
                'leave_days' => $this->cleanFloat($row['请假天数'] ?? 0),
                'deducted_leave_pay' => $this->cleanFloat($row['扣请假工资'] ?? 0),
                'payable_salary' => $this->cleanFloat($row['应付工资'] ?? 0),
                'social_security' => $this->cleanFloat($row['社保费'] ?? 0),
                'income_tax' => $this->cleanFloat($row['个人所得税'] ?? 0),
                'net_salary' => $this->cleanFloat($row['实收工资'] ?? 0),
            ];

            // 创建工资记录
            Salary::create($salaryData);
        }
    }

    /**
     * 清洗字符串
     */
    private function cleanString($value): string
    {
        return trim((string)$value);
    }

    /**
     * 清洗浮点数
     */
    private function cleanFloat($value): float
    {
        if (empty($value)) {
            return 0.00;
        }

        // 移除千分位逗号和空格
        $cleaned = str_replace([',', ' '], '', (string)$value);

        return round((float)$cleaned, 2);
    }
}
