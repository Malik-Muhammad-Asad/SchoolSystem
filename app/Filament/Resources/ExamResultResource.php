<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExamResultResource\Pages;
use App\Filament\Resources\ExamResultResource\RelationManagers;
use App\Models\Classes;
use App\Models\ExamResult;
use App\Models\test;
use Filament\Forms;
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

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('')
                    ->schema([

                        Forms\Components\Select::make('term_id')
                            ->label('Term')
                            ->relationship('term', 'name')
                            ->required(),

                        Forms\Components\Select::make('exam_id')
                            ->label('Exam')
                            ->relationship('exam', 'name')
                            ->required(),

                        Forms\Components\Select::make('subject_id')
                            ->label('Subject')
                            ->relationship('subject', 'name')
                            ->required(),

                        Forms\Components\Select::make('class_id')
                            ->label('Class')
                            ->relationship('class', 'name')
                            ->required()
                            ->reactive() // This makes it reactive to fetch students based on the selected class
                            // ->afterStateUpdated(function ($state, callable $set,callback $get) {
                            //     $subjectId = $get('subject_id');
                            //     $termId =   $get('term_id');
                            //     $examId =   $get('exam_id');


                            //     $students = \App\Models\ExamResult::where('class_id', $state)->get();

                            //     $students = \App\Models\Student::where('class_id', $state)->get();
                            //     $examResults = $students->map(fn($student) => [
                            //         'student_name' => $student->name,
                            //         'father_name' => $student->father_name,
                            //         'student_id' => $student->id,
                            //     ])->toArray();
                            //     $set('exam_results', $examResults);
                            // }),
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $subjectId = $get('subject_id');
                                $termId = $get('term_id');
                                $examId = $get('exam_id');
                        
                                // Fetch students based on the selected class
                                $students = \App\Models\Student::where('class_id', $state)->get();
                        
                                // Get existing exam results for the selected class, term, subject, and exam
                                $existingExamResults = \App\Models\ExamResult::where('class_id', $state)
                                    ->where('subject_id', $subjectId)
                                    ->where('term_id', $termId)
                                    ->where('exam_id', $examId)
                                    ->get();
                        
                                // Prepare the exam results array
                                $examResults = $students->map(function ($student) use ($existingExamResults) {
                                    // Check if the student has existing exam results
                                    $examResult = $existingExamResults->firstWhere('student_id', $student->id);
                        
                                    if ($examResult) {
                                        // If exam result exists, use the existing data
                                        return [
                                            'student_name' => $student->name,
                                            'father_name' => $student->father_name,
                                            'student_id' => $student->id,
                                            'obtain_number' => $examResult->obtain_number, // Set the obtained number if data exists
                                        ];
                                    } else {
                                        // If no exam result exists, set default value for obtain_number
                                        return [
                                            'student_name' => $student->name,
                                            'father_name' => $student->father_name,
                                            'student_id' => $student->id,
                                            'obtain_number' => 0, // Default value of 0 for obtain number
                                        ];
                                    }
                                })->toArray();
                        
                                // Set the exam results to the form
                                $set('exam_results', $examResults);
                            }),
                        Forms\Components\TextInput::make('subject_number')
                            ->label('Subject Number')
                            ->numeric()
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
                                    ->label('Subject Number')
                                    ->disabled()
                                    ->required(),

                                Forms\Components\TextInput::make('obtain_number')
                                    ->label('Obtain Number')
                                    ->numeric()
                                    ->required(),
                            ])
                            ->deletable(false)
                            ->addable(false)
                            ->columns(3)
                            ->columnSpanFull()
                            ->orderColumn(false)
                    ]),
                // Disable removing students manually
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            // ->query(ExamResult::query())
            ->columns([
                Tables\Columns\TextColumn::make('serial')->label('S/N')
                    ->getStateUsing(fn($rowLoop) => $rowLoop->index + 1),
                Tables\Columns\TextColumn::make('student.name')->label('Student Name'),
                Tables\Columns\TextColumn::make('student.father_name')->label('Father Name'),
                Tables\Columns\TextColumn::make('class.name')->label('Class'),
                Tables\Columns\TextColumn::make('term.name')->label('Term'),
                Tables\Columns\TextColumn::make('exam.name')->label('Exam'),
                Tables\Columns\TextColumn::make('subject.name')->label('Subject'),
                Tables\Columns\TextColumn::make('subject_number')->label('Subject Number'),
                Tables\Columns\TextColumn::make('obtain_number')->label('Obtain Number'),
            ])
            ->filters(
                [
                    Tables\Filters\SelectFilter::make('class')
                        ->label('Class')
                        ->relationship('class', 'name'),
                    Tables\Filters\SelectFilter::make('term')
                        ->label('Term')
                        ->relationship('term', 'name'),
                    Tables\Filters\SelectFilter::make('exam')
                        ->label('Exam')
                        ->relationship('exam', 'name'),
                    Tables\Filters\SelectFilter::make('subject')
                        ->label('Subject')
                        ->relationship('subject', 'name'),
                ],
                layout: FiltersLayout::AboveContent
            )

            ->actions([])

            ->bulkActions([])
            ->defaultSort('id', 'desc');
    }



    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExamResults::route('/'),
            'create' => Pages\CreateExamResult::route('/create'),
            'edit' => Pages\EditExamResult::route('/{record}/edit'),
        ];
    }
}
