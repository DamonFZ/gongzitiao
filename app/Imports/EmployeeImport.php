<?php

namespace App\Imports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;

class EmployeeImport implements ToModel, WithStartRow
{
    /**
     * 设置起始行为第 2 行，跳过第 1 行表头
     */
    public function startRow(): int
    {
        return 2;
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Excel 列顺序：姓名(0)、手机号(1)、身份证号(2)
        // 使用 updateOrCreate 防止重复导入，以身份证号为唯一标识
        return Employee::updateOrCreate(
            ['id_card' => $row[2]],  // 条件：身份证号
            ['name' => $row[0], 'phone' => $row[1]]  // 更新或创建的字段
        );
    }
}
