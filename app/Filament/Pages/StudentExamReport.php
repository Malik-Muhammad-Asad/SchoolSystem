<?php

namespace App\Filament\Pages;

use App\Models\Classes;
use App\Models\Exam;
use App\Models\Term;
use Filament\Forms;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class StudentExamReport extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    public $class = null;
    public $term = null;
    public $exams = [];
    public $scores = [];
    public $subjects = [];

    protected static string $view = 'filament.pages.student-exam-report';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public function mount()
    {
        // Initial values for form fields
        $this->form->fill([
            'class' => null,
            'term' => null,
            'exams' => [],
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Grid::make() // Using default grid (auto-determines the number of columns)
                ->schema([
                    Forms\Components\Select::make('class')
                        ->label('Class')
                        ->options(Classes::pluck('name', 'id'))
                        ->placeholder('Select a Class')
                        ->required()
                        ->columnSpan(4), // Span 4 columns
    
                    Forms\Components\Select::make('term')
                        ->label('Term')
                        ->options(Term::pluck('name', 'id'))
                        ->placeholder('Select a Term')
                        ->required()
                        ->columnSpan(4), // Span 4 columns
    
                    Forms\Components\MultiSelect::make('exams')
                        ->label('Exams')
                        ->options(Exam::pluck('name', 'id'))
                        ->placeholder('Select Exams')
                        ->required()
                        ->columnSpan(4), // Span 4 columns
                ]),
        ];
    }
    


    public function search()
    {
        $this->validate();

        $this->subjects = DB::table('subjects')
            ->where('class_id', $this->class)
            ->get();

        $students = DB::table('students')
            ->where('class', $this->class)
            ->get();

        $results = DB::table('exam_results')
            ->where('class_id', $this->class)
            ->where('term_id', $this->term)
            ->whereIn('exam_id', $this->exams)
            ->get()
            ->groupBy('student_id');

        $this->scores = $students->map(function ($student) use ($results) {
            $studentScores = ['name' => $student->name];
            foreach ($this->subjects as $subject) {
                $examScores = $results->get($student->id)
                    ->where('subject_id', $subject->id)
                    ->pluck('obtain_number', 'exam_id')
                    ->toArray();

                $studentScores[$subject->name] = [
                    'exams' => $examScores,
                    'total' => array_sum($examScores),
                ];
            }
            return $studentScores;
        });
    }
}
