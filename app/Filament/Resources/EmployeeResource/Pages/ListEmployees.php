<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('import')
                ->label('导入员工')
                ->color('success')
                ->form([
                    FileUpload::make('file')
                        ->label('上传 Excel 文件')
                        ->disk('local')
                        ->directory('imports')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel'
                        ])
                        ->required(),
                ])
                ->action(function (array $data) {
                    // 拼接文件的绝对物理路径
                    $absolutePath = storage_path('app/' . $data['file']);
                    
                    // 执行导入
                    Excel::import(new \App\Imports\EmployeeImport, $absolutePath);
                    
                    // 导入完成后删除物理文件，释放空间
                    @unlink($absolutePath);
                    
                    Notification::make()
                        ->title('员工导入成功')
                        ->success()
                        ->send();
                }),
        ];
    }
}
