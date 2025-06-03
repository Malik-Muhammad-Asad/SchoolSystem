<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AcademicYearResource\Pages;
use App\Filament\Resources\AcademicYearResource\RelationManagers;
use App\Models\AcademicYear;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AcademicYearResource extends Resource
{
    protected static ?string $model = AcademicYear::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
     protected static ?string $navigationGroup = 'Setup';

   public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\TextInput::make('year')
                ->required()
                ->maxLength(9)
                ->label('Academic Year (e.g. 2024-2025)'),

            Forms\Components\DatePicker::make('start_date')
                ->required()
                ->label('Start Date'),

            Forms\Components\DatePicker::make('end_date')
                ->required()
                ->label('End Date'),

            Forms\Components\Toggle::make('is_current')
                ->label('Current Academic Year'),
        ]);
}


    public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('year')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('start_date')
                ->date()
                ->sortable(),

            Tables\Columns\TextColumn::make('end_date')
                ->date()
                ->sortable(),

            Tables\Columns\IconColumn::make('is_current')
                ->boolean()
                ->label('Current'),
        ])
        ->filters([
            //
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
            'index' => Pages\ListAcademicYears::route('/'),
            'create' => Pages\CreateAcademicYear::route('/create'),
            'edit' => Pages\EditAcademicYear::route('/{record}/edit'),
        ];
    }
}
