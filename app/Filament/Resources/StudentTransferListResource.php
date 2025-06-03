<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentTransferListResource\Pages;
use App\Models\Student;
use App\Models\Classes;
use App\Models\AcademicYear;
use App\Models\StudentTransfer;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Form;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Illuminate\Support\Collection;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Notifications\Notification;
use Filament\Tables\Enums\FiltersLayout;

class StudentTransferListResource extends Resource
{
    protected static ?string $model = Student::class;
    protected static ?string $navigationLabel = 'Student Transfer Management';
    protected static ?string $modelLabel = 'Student Transfer';
    protected static ?string $navigationGroup = 'Student Management';
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    public static function form(Form $form): Form
    {
        return $form;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // TextColumn::make('gr_no')
                //     ->label('GR No')
                //     ->sortable()
                //     ->searchable(),
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('father_name')
                    ->label('Father Name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('classes.name')
                    ->label('Current Class')
                    ->sortable(),
                TextColumn::make('classes.academicYear.year')
                    ->label('Academic Year')
                    ->sortable(),
            ])
            ->filters([
                // SelectFilter::make('academic_year_id')
                //     ->label('Academic Year')
                //     ->options(AcademicYear::pluck('year', 'id'))
                //     ->searchable()
                //     ->query(function ($query, array $data) {
                //         if (!empty($data['value'])) {
                //             $query->whereHas('classes', function ($q) use ($data) {
                //                 $q->where('academic_year_id', $data['value']);
                //             });
                //         }
                //     }),

                SelectFilter::make('class_id')
                    ->label('Class')
                    ->options(Classes::with('academicYear')->get()->mapWithKeys(function ($class) {
                        return [$class->id => $class->academicYear->year . ' - ' . $class->name];
                    }))
                    ->searchable()
                    ->query(function ($query, array $data) {
                        if (!empty($data['value'])) {
                            $query->where('class_id', $data['value']);
                        }
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(2)
            ->persistFiltersInSession()
            ->actions([])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('transfer')
                        ->label('Transfer Selected Students')
                        ->form([
                            Select::make('academic_year_id')
                                ->label('To Academic Year')
                                ->required()
                                ->options(AcademicYear::pluck('year', 'id'))
                                ->live()
                                ->searchable()
                                ->afterStateUpdated(fn(callable $set) => $set('to_class_id', null)),

                            Select::make('to_class_id')
                                ->label('To Class')
                                ->required()
                                ->options(function (callable $get) {
                                    $yearId = $get('academic_year_id');
                                    if (!$yearId) return [];

                                    return Classes::where('academic_year_id', $yearId)
                                        ->pluck('name', 'id');
                                })
                                ->searchable(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            foreach ($records as $student) {
                                StudentTransfer::create([
                                    'student_id' => $student->id,
                                    'from_class_id' => $student->class_id,
                                    'to_class_id' => $data['to_class_id'],
                                    'academic_year_id' => $data['academic_year_id'],
                                    'transfer_date' => now(),
                                ]);

                                $student->update([
                                    'class_id' => $data['to_class_id'],
                                ]);
                            }

                            Notification::make()
                                ->title('Students Transferred Successfully')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->modifyQueryUsing(function ($query) {
                // Remove this as filters now handle their own queries
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentTransferLists::route('/'),
        ];
    }
}