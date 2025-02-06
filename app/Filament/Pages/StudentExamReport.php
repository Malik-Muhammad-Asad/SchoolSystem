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
    public $examNames = [];


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
        $this->subjects = DB::table('subjects')->get() ?? collect([]);
        $students = DB::table('students')
            ->where('class_id', $this->class)
            ->get();
        $results = DB::table('exam_results')
            ->where('class_id', $this->class)
            ->where('term_id', $this->term)
            ->whereIn('exam_id', $this->exams)
            ->get()
            ->groupBy('student_id');
    
        $this->examNames = Exam::whereIn('id', $this->exams)
            ->pluck('name', 'id')
            ->toArray();
    
        $this->scores = $students->map(function ($student) use ($results) {
            $studentScores = ['name' => $student->name];
            
            $totalScore = 0;
            $totalMaxScore = 0; // Initialize max score calculation for all subjects
    
            foreach ($this->subjects as $subject) {
                // Get subject max score (subject_number) from exam_results table
                $subjectMaxScore = DB::table('exam_results')
                    ->where('class_id', $this->class)
                    ->where('subject_id', $subject->id)
                    ->where('term_id', $this->term)
                    ->value('subject_number') ?? 0; // Get the max score (subject_number) for the subject
    
                // Get scores for the student and subject, default to 0 if missing
                $examScores = optional($results->get($student->id))
                    ->where('subject_id', $subject->id)
                    ->pluck('obtain_number', 'exam_id')
                    ->toArray();
    
                // Ensure we fill in all possible exams and set default 0 if no score exists
                $examScores = array_replace(array_fill_keys($this->exams, 0), $examScores);
    
                // Calculate total score for the subject
                $subjectTotalScore = array_sum($examScores);
    
                // Add subject total score to the overall total score
                $totalScore += $subjectTotalScore;
                $totalMaxScore += $subjectMaxScore; // Add max score for the subject to the overall max score
    
                // Store the scores and total for each subject
                $studentScores[$subject->name] = [
                    'exams' => $examScores,
                    'total' => $subjectTotalScore, // Subject total score
                ];
            }
    
            // Calculate overall percentage
            $percentage = ($totalScore / $totalMaxScore) * 100;
    
            // Determine grade based on percentage
            $grade = $this->getGrade($percentage);
    
            // Add the total, percentage, and grade to the student data
            $studentScores['total'] = $totalScore;
            $studentScores['percentage'] = $percentage;
            $studentScores['grade'] = $grade;

            return $studentScores;
        });
    }
    
    
    
    /**
     * Function to determine the grade based on percentage
     */
    private function getGrade($percentage)
    {
        if ($percentage >= 80) {
            return 'A';
        } elseif ($percentage >= 60) {
            return 'B';
        } elseif ($percentage >= 40) {
            return 'C';
        } else {
            return 'F';
        }
    }
    
}
