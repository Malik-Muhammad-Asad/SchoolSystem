<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExamResultResource\Pages;
use App\Filament\Resources\ExamResultResource\RelationManagers;
use App\Models\Classes;
use App\Models\ExamResult;
use App\Models\AcademicYear;
use App\Models\student;
use App\Models\test;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Layout;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExamResultResource extends Resource
{
    protected static ?string $model = ExamResult::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Mask Compile';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('')
                    ->schema([

                        Forms\Components\Select::make('class_id')
                            ->label('Class')
                            ->options(function () {
                                $currentYearId = AcademicYear::where('is_current', true)->value('id');
                                return Classes::where('academic_year_id', $currentYearId)
                                    ->pluck('name', 'id');
                            })
                            ->required()
                            ->live()
                            ->disabled(fn($record): bool => $record ? true : false)
                            ->afterStateUpdated(function (callable $set, $get) {
                                $set('subject_id', null);
                                self::updateExamResults($set, $get);
                            }),
                        Forms\Components\Hidden::make('class_id'),

                        Forms\Components\Select::make('subject_id')
                            ->label('Subject')
                            ->options(function (callable $get) {
                                $classId = $get('class_id');
                                if (!$classId) {
                                    return [];
                                }
                                return Classes::find($classId)?->subjects()->pluck('name', 'subjects.id') ?? [];
                            })
                            ->required()
                            ->live()
                            ->disabled(fn($record): bool => $record ? true : false)
                            ->afterStateUpdated(function (callable $set, $get) {
                                self::updateExamResults($set, $get);
                            }),
                        Forms\Components\Hidden::make('subject_id'),

                        Forms\Components\Select::make('term_id')
                            ->label('Term')
                            ->relationship('term', 'name')
                            ->required()
                            ->live()
                            ->disabled(fn($record): bool => $record ? true : false)
                            ->afterStateUpdated(function (callable $set, $get) {
                                $set('exam_id', null);
                                self::updateExamResults($set, $get);
                            }),
                        Forms\Components\Hidden::make('term_id'),

                        Forms\Components\Select::make('exam_id')
                            ->label('Exam')
                            ->options(function (callable $get) {
                                $termId = $get('term_id');
                                if (!$termId) {
                                    return [];
                                }
                                return \App\Models\Exam::where('term_id', $termId)->pluck('name', 'id');
                            })
                            ->required()
                            ->live()
                            ->disabled(fn($record): bool => $record ? true : false)
                            ->afterStateUpdated(function (callable $set, $get) {
                                self::updateExamResults($set, $get);
                            }),
                        Forms\Components\Hidden::make('exam_id'),

                        Forms\Components\TextInput::make('subject_number')
                            ->label('Subject Number')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                    ])
                    ->columns(3),
                Forms\Components\Section::make('Student List')
                    ->schema([
                        Forms\Components\Repeater::make('exam_results')
                            ->label('')
                            ->reactive()
                            ->schema([
                                Forms\Components\TextInput::make('student_name')
                                    ->label('Student Name')
                                    ->disabled(),

                                Forms\Components\Hidden::make('student_id')
                                    ->label('Student ID'),

                                Forms\Components\TextInput::make('father_name')
                                    ->label('Father Name')
                                    ->disabled()
                                    ->required(),

                                Forms\Components\TextInput::make('obtain_number')
                                    ->label('Obtain Number')
                                    ->numeric()
                                    ->required()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        // Fetch subject_number properly (outside the Repeater)
                                        $subjectNumber = $get('../../subject_number') ?? 0;

                                        if ($state > $subjectNumber) {
                                            // Show a toast notification
                                            Notification::make()
                                                ->title('Error')
                                                ->body('Obtain Number cannot be greater than Subject Number!')
                                                ->danger()
                                                ->send();

                                            // Reset obtain_number to 0
                                            $set('obtain_number', 0);
                                        }
                                    }),

                            ])
                            ->deletable(false)
                            ->addable(false)
                            ->columns(3)
                            ->columnSpanFull()
                            ->orderColumn(false),
                    ]),


                // Disable removing students manually
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->query(
                ExamResult::query()
                    ->selectRaw('MIN(id) as id, class_id, term_id, exam_id, subject_id, subject_number')
                    ->groupBy('class_id', 'term_id', 'exam_id', 'subject_id', 'subject_number')
            )
            ->columns([
                Tables\Columns\TextColumn::make('serial')->label('S/N')
                    ->getStateUsing(fn($rowLoop) => $rowLoop->index + 1),

                Tables\Columns\TextColumn::make('class.name')->label('Class'),
                Tables\Columns\TextColumn::make('term.name')->label('Term'),
                Tables\Columns\TextColumn::make('exam.name')->label('Exam'),
                Tables\Columns\TextColumn::make('subject.name')->label('Subject'),
                Tables\Columns\TextColumn::make('subject_number')->label('Subject Number'),
            ])
            ->filters(
                [
                    Tables\Filters\Filter::make('result_filters')
                        ->form([
                            Forms\Components\Grid::make([
                                'default' => 1,
                                'sm' => 2,
                                'md' => 3,
                                'lg' => 4,
                            ])
                                ->schema([
                                    Forms\Components\Select::make('class_id')
                                        ->label('Class')
                                        ->options(function () {
                                            $currentYearId = \App\Models\AcademicYear::where('is_current', true)->value('id');
                                            return \App\Models\Classes::where('academic_year_id', $currentYearId)
                                                ->pluck('name', 'id');
                                        })
                                        ->live(),

                                    Forms\Components\Select::make('subject_id')
                                        ->label('Subject')
                                        ->options(function (callable $get) {
                                            $classId = $get('class_id');
                                            if (!$classId) {
                                                return [];
                                            }
                                            return \App\Models\Classes::find($classId)?->subjects()->pluck('name', 'subjects.id') ?? [];
                                        })
                                        ->live(),

                                    Forms\Components\Select::make('term_id')
                                        ->label('Term')
                                        ->options(\App\Models\Term::pluck('name', 'id'))
                                        ->live()
                                        ->afterStateUpdated(fn(callable $set) => $set('exam_id', null)),

                                    Forms\Components\Select::make('exam_id')
                                        ->label('Exam')
                                        ->options(function (callable $get) {
                                            $termId = $get('term_id');
                                            if (!$termId) {
                                                return [];
                                            }
                                            return \App\Models\Exam::where('term_id', $termId)->pluck('name', 'id');
                                        })
                                        ->live(),
                                ])
                                ->columnSpanFull(),
                        ])
                        ->columnSpanFull()
                        ->query(function (Builder $query, array $data): Builder {
                            return $query
                                ->when($data['class_id'], fn (Builder $query, $value) => $query->where('class_id', $value))
                                ->when($data['subject_id'], fn (Builder $query, $value) => $query->where('subject_id', $value))
                                ->when($data['term_id'], fn (Builder $query, $value) => $query->where('term_id', $value))
                                ->when($data['exam_id'], fn (Builder $query, $value) => $query->where('exam_id', $value));
                        })
                        ->indicateUsing(function (array $data): array {
                            $indicators = [];
                            if ($data['class_id'] ?? null) {
                                $indicators[] = 'Class: ' . \App\Models\Classes::find($data['class_id'])?->name;
                            }
                            if ($data['subject_id'] ?? null) {
                                $indicators[] = 'Subject: ' . \App\Models\Subject::find($data['subject_id'])?->name;
                            }
                            if ($data['term_id'] ?? null) {
                                $indicators[] = 'Term: ' . \App\Models\Term::find($data['term_id'])?->name;
                            }
                            if ($data['exam_id'] ?? null) {
                                $indicators[] = 'Exam: ' . \App\Models\Exam::find($data['exam_id'])?->name;
                            }
                            return $indicators;
                        }),
                ],
                layout: FiltersLayout::AboveContent
            )

            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('View Record Details')  // Customize the modal button text
                    ->modalWidth('lg'), // Adjust the modal width (e.g., 'sm', 'md', 'lg', 'xl', 'full'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Delete Exam Result Group')
                    ->modalDescription('Are you sure you want to delete all exam results for this specific class, term, exam, and subject? This action cannot be undone.')
                    ->action(function ($record) {
                        ExamResult::where('class_id', $record->class_id)
                            ->where('term_id', $record->term_id)
                            ->where('exam_id', $record->exam_id)
                            ->where('subject_id', $record->subject_id)
                            ->where('subject_number', $record->subject_number)
                            ->delete();

                        Notification::make()
                            ->title('Exam results deleted successfully')
                            ->success()
                            ->send();
                    }),
            ])

            ->bulkActions([])
            ->defaultSort('id', 'desc');
    }
    public static function updateExamResults(callable $set, callable $get)
    {
        $subjectId = $get('subject_id');
        $termId = $get('term_id');
        $examId = $get('exam_id');
        $classId = $get('class_id');


        if (!$classId || !$subjectId || !$termId || !$examId || !$classId) {
            $set('exam_results', []);
            $set('subject_number', 0);
            return;
        }


        $students = student::where('class_id', $classId)->get();

        $existingExamResults = ExamResult::where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('term_id', $termId)
            ->where('exam_id', $examId)
            ->get();
        if ($existingExamResults->isNotEmpty()) {
            $set('subject_number', $existingExamResults[0]->subject_number);
        } else {
            $set('subject_number', 0);
        }

        $examResults = $students->map(function ($student) use ($existingExamResults) {
            $examResult = $existingExamResults->firstWhere('student_id', $student->id);

            return [
                'id'=> $examResult ? $examResult->id : null,
                'student_name' => $student->name,
                'father_name' => $student->father_name,
                'student_id' => $student->id,
                'obtain_number' => $examResult ? $examResult->obtain_number : 0,
            ];
        })->toArray();

        $set('exam_results', $examResults);
    }



    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExamResults::route('/'),
            'create' => Pages\CreateExamResult::route('/create'),
            'view' => Pages\ViewExamResults::route('/{record}'),
            'edit' => Pages\EditExamResult::route('/{record}/edit'),
        ];
    }
}
