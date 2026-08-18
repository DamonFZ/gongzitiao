<?php

namespace App\Filament\Actions;

use App\Imports\SalaryImport;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Maatwebsite\Excel\Facades\Excel;

class ImportSalaryAction
{
    public static function make(): Action
    {
        return Action::make('importSalary')
            ->label('导入工资表')
            ->icon('heroicon-o-arrow-up-tray')
            ->form([
                FileUpload::make('file')
                    ->label('Excel 文件')
                    ->acceptedFileTypes(['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                    ->required()
                    ->helperText('支持 .xls 和 .xlsx 格式'),
            ])
            ->action(function (array $data) {
                /** @var TemporaryUploadedFile $file */
                $file = $data['file'];
                
                try {
                    Excel::import(new SalaryImport(), $file);
                    
                    Notification::make()
                        ->title('导入成功')
                        ->success()
                        ->send();
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('导入失败')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            })
            ->modalHeading('导入工资表')
            ->modalButton('开始导入')
            ->modalWidth('md');
    }
}
