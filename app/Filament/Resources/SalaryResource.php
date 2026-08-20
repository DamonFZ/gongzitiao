<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalaryResource\Pages;
use App\Filament\Resources\SalaryResource\RelationManagers;
use App\Models\Salary;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SalaryResource extends Resource
{
    protected static ?string $model = Salary::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('employee_id')
                    ->relationship('employee', 'name')
                    ->required()
                    ->label('员工'),
                Forms\Components\TextInput::make('month')
                    ->required()
                    ->label('月份(如2026-06)')
                    ->maxLength(7),
                Forms\Components\TextInput::make('department')
                    ->label('部门')
                    ->maxLength(255),
                Forms\Components\TextInput::make('position')
                    ->label('职位')
                    ->maxLength(255),
                Forms\Components\TextInput::make('base_salary')
                    ->label('基本工资')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('position_allowance')
                    ->label('岗位津贴')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('overtime_pay')
                    ->label('加班费')
                    ->numeric(),
                Forms\Components\TextInput::make('leave_days')
                    ->label('请假天数')
                    ->numeric(),
                Forms\Components\TextInput::make('deducted_leave_pay')
                    ->label('扣除请假工资')
                    ->numeric(),
                Forms\Components\TextInput::make('payable_salary')
                    ->label('应发工资')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('social_security')
                    ->label('社保')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('income_tax')
                    ->label('个税')
                    ->numeric(),
                Forms\Components\TextInput::make('net_salary')
                    ->label('实发工资')
                    ->numeric()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.name')
                    ->label('员工姓名')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('month')
                    ->label('月份')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('department')
                    ->label('部门')
                    ->searchable(),
                Tables\Columns\TextColumn::make('net_salary')
                    ->label('实收工资')
                    ->money('CNY')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('month')
                    ->label('月份')
                    ->options(Salary::select('month')->distinct()->pluck('month', 'month')->toArray()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalaries::route('/'),
            'create' => Pages\CreateSalary::route('/create'),
            'edit' => Pages\EditSalary::route('/{record}/edit'),
        ];
    }
}
