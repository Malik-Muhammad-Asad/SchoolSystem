<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Filament\Resources\StudentResource\RelationManagers;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use App\Models\AcademicYear;
use App\Models\Classes;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Select;
use Illuminate\Support\Collection;
use Filament\Notifications\Notification;

use Illuminate\Database\Eloquent\SoftDeletingScope;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Student';
    protected static ?string $navigationGroup = 'Student Management';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('gr_no')
                    ->required()
                    ->unique()
                    ->maxLength(255),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('father_name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Select::make('class_id')
                    ->label('class')
                    ->required()
                    ->options(function () {
                        $currentYearId = AcademicYear::where('is_current', true)->value('id');

                        return Classes::where('academic_year_id', $currentYearId)
                            ->pluck('name', 'id');
                    })
                    ->preload()
                    ->searchable(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sno')
                    ->label('Sno')
                    ->state(fn($rowLoop) => $rowLoop->iteration)
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('father_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('gr_no')
                    ->searchable(),
                Tables\Columns\TextColumn::make('classes.name')
                    ->numeric()
                    ->sortable(),
                BadgeColumn::make('is_active')
                    ->label('Active')
                    ->formatStateUsing(fn(bool $state) => $state ? 'Active' : 'Inactive')
                    ->color(fn(bool $state) => $state ? 'success' : 'danger')
                    ->sortable(),


                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters(
                filters: [
                    Tables\Filters\SelectFilter::make('class_id')
                        ->label('Class')
                        ->options(function () {
                            $currentYearId = AcademicYear::where('is_current', true)->value('id');

                            return Classes::where('academic_year_id', $currentYearId)
                                ->pluck('name', 'id');
                        }),
                    Tables\Filters\SelectFilter::make('is_active')
                        ->label('Active Status')
                        ->options([
                            1 => 'Active',
                            0 => 'Inactive',
                        ]),

                ],
                layout: FiltersLayout::AboveContent
            )
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([


                    Tables\Actions\BulkAction::make('change_status')
                        ->label('Change Active Status')    // Bulk action dropdown label
                        ->icon('heroicon-o-user-circle')   // Icon for the bulk action
                        ->form([
                            Select::make('is_active')
                                ->label('Select Status')    // Label above dropdown in modal
                                ->options([
                                    1 => 'Active',
                                    0 => 'Inactive',
                                ])
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $records->each->update(['is_active' => $data['is_active']]);

                            Notification::make()
                                ->title("Selected students marked as " . ($data['is_active'] ? 'Active' : 'Inactive'))
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),
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
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes(['onlyActiveStudents']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'view' => Pages\ViewStudent::route('/{record}'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }
}
