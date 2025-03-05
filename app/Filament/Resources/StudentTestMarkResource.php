<?php
namespace App\Filament\Resources;

use App\Filament\Resources\StudentTestMarkResource\Pages;
use App\Models\Classes;
use App\Models\StudentTestMark;
use App\Models\Student;
use App\Models\Term;
use App\Models\Subject;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class StudentTestMarkResource extends Resource
{
    protected static ?string $model = StudentTestMark::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Student Test Marks';
    protected static ?string $pluralLabel = 'Student Test Marks';
    protected static ?string $slug = 'student-test-marks';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Section::make('Select Exam Details')
                ->schema([
                    Select::make('term_id')
                        ->label('Term')
                        ->relationship('term', 'name')
                        ->required()
                        ->reactive()
                        ->disabled(fn($record): bool => $record ? true : false)
                        ->afterStateUpdated(fn(callable $set, $get) => self::updateStudentList($set, $get)),

                    Select::make('subject_id')
                        ->label('Subject')
                        ->relationship('subject', 'name')
                        ->required()
                        ->reactive()
                        ->disabled(fn($record): bool => $record ? true : false)
                        ->afterStateUpdated(fn(callable $set, $get) => self::updateStudentList($set, $get)),

                    Select::make('class_id')
                        ->label('Class')
                        ->relationship('class', 'name')
                        ->required()
                        ->reactive()
                        ->disabled(fn($record): bool => $record ? true : false)
                        ->afterStateUpdated(fn(callable $set, $get) => self::updateStudentList($set, $get)),

                    TextInput::make('subject_number')
                        ->label('Max Marks')
                        ->numeric()
                        ->required(),
                ])
                ->columns(3),

            Forms\Components\Section::make('Student List')
                ->schema([
                    Repeater::make('test_marks')
                        ->label('')
                        ->reactive()
                        ->schema([
                            Forms\Components\Placeholder::make('serial_no')
                                ->label('S/N')
                                ->content(fn($get) => array_search($get('student_id'), array_column($get('../../test_marks') ?? [], 'student_id')) + 1)
                                ->columns(1),

                            TextInput::make('student_name')
                                ->label('Student Name')
                                ->disabled()
                                ->columns(1),
                            TextInput::make('father_name')
                                ->label('Father Name')
                                ->disabled()
                                ->required()
                                ->columns(4),

                            Forms\Components\Hidden::make('student_id'),

                            TextInput::make('obtain_number')
                                ->label('Obtain Number')
                                ->numeric()
                                ->required()
                                ->afterStateUpdated(function ($state, $set, $get) {
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
                                })
                                ->columns(3),

                        ])
                        ->deletable(false)
                        ->addable(false)
                        ->columns(4)
                        ->orderColumn(false)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function updateStudentList(callable $set, callable $get)
    {
        $classId = $get('class_id');
        $subjectId = $get('subject_id');
        $termId = $get('term_id');

        if (!$classId || !$subjectId || !$termId) {
            $set('test_marks', []);
            return;
        }

        $students = Student::where('class_id', $classId)->get();

        $existingMarks = StudentTestMark::where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('term_id', $termId)
            ->get();

        $testMarks = $students->map(function ($student) use ($existingMarks) {
            $mark = $existingMarks->firstWhere('student_id', $student->id);
            return [
                'student_name' => $student->name,
                'father_name' => $student->father_name,
                'student_id' => $student->id,
                'obtain_number' => $mark ? $mark->obtain_number : 0,
            ];
        })->toArray();

        $set('test_marks', $testMarks);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.name')->label('Student')->sortable(),
                Tables\Columns\TextColumn::make('class.name')->label('Class')->sortable(),
                Tables\Columns\TextColumn::make('subject.name')->label('Subject')->sortable(),
                Tables\Columns\TextColumn::make('term.name')->label('Term')->sortable(),
                Tables\Columns\TextColumn::make('obtain_number')->label('Marks Obtained'),
                Tables\Columns\TextColumn::make('subject_number')->label('Max Marks'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('class_id')->label('Class')->relationship('class', 'name'),
                Tables\Filters\SelectFilter::make('subject_id')->label('Subject')->relationship('subject', 'name'),
                Tables\Filters\SelectFilter::make('term_id')->label('Term')->relationship('term', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentTestMarks::route('/'),
            'create' => Pages\CreateStudentTestMark::route('/create'),
            'edit' => Pages\EditStudentTestMark::route('/{record}/edit'),
        ];
    }
}
