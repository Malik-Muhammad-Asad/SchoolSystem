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
        $this->form->fill([
            'class' => null,
            'term' => null,
            'exams' => [],
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Grid::make()
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('class')
                        ->label('Class')
                        ->options(Classes::pluck('name', 'id'))
                        ->placeholder('Select a Class')
                        ->required(),

                    Forms\Components\Select::make('term')
                        ->label('Term')
                        ->options(Term::pluck('name', 'id'))
                        ->placeholder('Select a Term')
                        ->required(),

                    Forms\Components\MultiSelect::make('exams')
                        ->label('Exams')
                        ->options(Exam::pluck('name', 'id'))
                        ->placeholder('Select Exams')
                        ->required(),
                ]),
        ];
    }

    public function search()
    {
        
        // Validate form inputs
       

        // Fetch data
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

        // Process scores
        $this->scores = $students->map(function ($student) use ($results) {
            $studentScores = ['name' => $student->name];
            $totalScore = 0;
            $totalMaxScore = 0;
            foreach ($this->subjects as $subject) {
                $subjectMaxScore = $this->getSubjectMaxScore($subject->id);
                $examScores = $this->getExamScores($results->get($student->id), $subject->id);
                $subjectTotalScore = array_sum($examScores);

                $totalScore += $subjectTotalScore;
                $totalMaxScore += $subjectMaxScore;

                $studentScores[$subject->name] = [
                    'exams' => $examScores,
                    'total' => $subjectTotalScore,
                ];
            }
            $percentage = $this->calculatePercentage($totalScore, $totalMaxScore);
            $grade = $this->getGrade($percentage);

            $studentScores['total'] = $totalScore;
            $studentScores['percentage'] = $percentage;
            $studentScores['grade'] = $grade;

            return $studentScores;
        });
    }

    private function getSubjectMaxScore($subjectId)
    {
        return DB::table('exam_results')
            ->where('class_id', $this->class)
            ->where('subject_id', $subjectId)
            ->where('term_id', $this->term)
            ->value('subject_number') ?? 0;
    }

    private function getExamScores($studentResults, $subjectId)
    {
        return optional($studentResults)
            ->where('subject_id', $subjectId)
            ->pluck('obtain_number', 'exam_id')
            ->toArray();
    }

    private function calculatePercentage($totalScore, $totalMaxScore)
    {
        return $totalMaxScore > 0 ? ($totalScore / $totalMaxScore) * 100 : 0;
    }
   
    private function getGrade($percentage)
    {
        if ($percentage >= 80) return 'A';
        if ($percentage >= 60) return 'B';
        if ($percentage >= 40) return 'C';
        return 'F';
    }
}