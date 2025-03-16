<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClassSubjectResource\Pages;
use App\Models\ClassSubject;
use App\Models\Subject;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;

class ClassSubjectResource extends Resource
{
    protected static ?string $model = ClassSubject::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Class Subjects';
    protected static ?string $navigationGroup = 'Setup';
    protected static ?int $navigationSort = 2;
    protected static ?string $pluralLabel = 'Class Subjects';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Select::make('class_id')
                ->label('Class')
                ->relationship('class', 'name')
                ->required(),
            Select::make('subject_id')
                ->label('Subject')
                ->multiple()
                ->searchable()
                ->options(Subject::pluck('name', 'id'))
                ->required(),

        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table->query(
            ClassSubject::query()
                ->selectRaw('MAX(class_subjects.id) AS id, class_id, MAX(c.name) as class_name, GROUP_CONCAT(subjects.name ORDER BY subjects.name SEPARATOR ", ") as subjects_list')
                ->join('subjects', 'class_subjects.subject_id', '=', 'subjects.id')
                ->join('class AS c', 'class_subjects.class_id', '=', 'c.id')
                ->groupBy('class_id')
                ->orderByRaw('MAX(class_subjects.id) ASC')
        )->columns([
                    TextColumn::make('class_name')->label('Class'),
                    TextColumn::make('subjects_list')->label('Subjects'),
                ])
            ->filters(
                [
                    Tables\Filters\SelectFilter::make('class')
                        ->label('Class')
                        ->relationship('class', 'name'),
                ],
                layout: FiltersLayout::AboveContent
            )

            ->actions([

                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort(null);

    }
    


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClassSubjects::route('/'),
            'create' => Pages\CreateClassSubject::route('/create'),
            'edit' => Pages\EditClassSubject::route('/{record}/edit'),

        ];
    }
}

