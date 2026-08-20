<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalaryImportErrorResource\Pages;
use App\Models\SalaryImportError;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SalaryImportErrorResource extends Resource
{
    protected static ?string $model = SalaryImportError::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationGroup = '数据管理';

    protected static ?int $navigationSort = 50;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // 只读资源，无需表单
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('month')
                    ->label('月份')
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('name')
                    ->label('员工姓名')
                    ->searchable(),
                Tables\Columns\TextColumn::make('department')
                    ->label('部门')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('error_reason')
                    ->label('失败原因')
                    ->searchable()
                    ->color('danger'),
                Tables\Columns\TextColumn::make('row_data')
                    ->label('原始数据')
                    ->formatStateUsing(function ($state) {
                        if (!is_array($state)) {
                            $state = is_string($state) ? json_decode($state, true) : [];
                        }
                        if (empty($state)) {
                            return '-';
                        }
                        // 只显示关键字段
                        $keys = ['姓名', '部门', '岗位', '基本工资', '实收工资'];
                        $parts = [];
                        foreach ($keys as $key) {
                            if (isset($state[$key]) && !empty($state[$key])) {
                                $parts[] = "{$key}: {$state[$key]}";
                            }
                        }
                        return implode(' | ', $parts) ?: json_encode(array_slice($state, 0, 3), JSON_UNESCAPED_UNICODE);
                    })
                    ->toggleable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('记录时间')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('month')
                    ->label('月份')
                    ->options(SalaryImportError::select('month')->distinct()->pluck('month', 'month')->toArray()),
                Tables\Filters\Filter::make('name')
                    ->label('员工姓名')
                    ->form([
                        Forms\Components\TextInput::make('name')->label('员工姓名'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when($data['name'], fn ($q) => $q->where('name', 'like', '%' . $data['name'] . '%'));
                    }),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSalaryImportErrors::route('/'),
        ];
    }
}
