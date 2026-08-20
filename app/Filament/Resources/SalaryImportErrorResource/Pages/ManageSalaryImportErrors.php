<?php

namespace App\Filament\Resources\SalaryImportErrorResource\Pages;

use App\Filament\Resources\SalaryImportErrorResource;
use Filament\Resources\Pages\ManageRecords;

class ManageSalaryImportErrors extends ManageRecords
{
    protected static string $resource = SalaryImportErrorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // 只读资源，移除创建按钮
        ];
    }
}
