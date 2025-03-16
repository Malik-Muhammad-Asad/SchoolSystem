<?php

namespace App\Filament\Pages;

use App\Models\Classes;
use App\Models\ClassSubject;
use App\Models\Exam;
use App\Models\StudentTestMark;
use App\Models\Term;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms;
use Filament\Notifications\Notification;
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
    public $ExtraExams = null;
    protected static string $view = 'filament.pages.student-exam-report';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public function mount()
    {
        $this->form->fill([
            'class' => null,
            'term' => null,
            'exams' => [],
            'ExtraExams' => null,
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
                        ->required()
                        ->reactive(),

                    Forms\Components\Select::make('term')
                        ->label('Term')
                        ->options(Term::pluck('name', 'id'))
                        ->placeholder('Select a Term')
                        ->required()
                        ->reactive(),

                    Forms\Components\MultiSelect::make('exams')
                        ->label('Exams')
                        ->options(Exam::pluck('name', 'id'))
                        ->placeholder('Select Exams')
                        ->required(),

                    Forms\Components\Select::make('ExtraExams')
                        ->label('Extra Number Add')
                        ->options(
                            fn($get) =>
                            StudentTestMark::where('class_id', $get('class'))
                                ->where('term_id', $get('term'))
                                ->groupBy('class_id', 'term_id')
                                ->orderByRaw('MIN(id) ASC')
                                ->pluck(DB::raw('ANY_VALUE(test_name)'), DB::raw('ANY_VALUE(test_name)'))
                        )

                        ->placeholder('Select Exams')
                        ->reactive(),
                ]),
        ];

    }

    public function search()
    {

        $subjectIds = ClassSubject::where('class_id', $this->class)->pluck('subject_id')->toArray();
        $this->subjects = !empty($subjectIds)
            ? DB::table('subjects')->whereIn('id', $subjectIds)->get()
            : collect([]);
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
            $studentScores = ['name' => $student->name, 'father_name' => $student->father_name,];
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


            $extraExamScores = $this->ExtraExams ? $this->extraNumberObtain($student->id) : (object) ['obtain_number' => 0, 'subject_number' => 0];

            $ExtraObtain = $extraExamScores->obtain_number;
            $ExtraMax = $extraExamScores->subject_number;
            $totalScore += $ExtraObtain;
            $totalMaxScore += $ExtraMax;

            $percentage = $this->calculatePercentage($totalScore, $totalMaxScore);
            $grade = $this->getGrade($percentage);
            $studentScores['ExtraObtain'] = $ExtraObtain;
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
            ->whereIn('exam_id', $this->exams)
            ->selectRaw('SUM(DISTINCT subject_number) as max_score') // Prevent duplicate summing
            ->value('max_score') ?? 0;
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
    private function extraNumberObtain($studentId)
    {
        return DB::table('student_test_marks')
            ->where('class_id', $this->class)
            ->where('term_id', $this->term)
            ->where('student_id', $studentId)
            ->where('test_name', $this->ExtraExams)
            ->select('obtain_number', 'subject_number')
            ->first() ?? ['obtain_number' => 0, 'subject_number' => 0];
    }

    private function getGrade($percentage)
    {
        if ($percentage >= 80)
            return 'A + 1';
        if ($percentage >= 70)
            return 'A';
        if ($percentage >= 60)
            return 'B';
        if ($percentage >= 50)
            return 'C';
        if ($percentage >= 40)
            return 'D';
        if ($percentage >= 30)
            return 'E';
        return 'F';
    }
    public function downloadPDF()
    {
        $className = Classes::find($this->class)->name ?? 'Class';
        $termName = Term::find($this->term)->name ?? 'Term';
        
        // Generate the PDF
        $pdf = PDF::loadView('exports.exam-report', [
            'scores' => $this->scores,
            'subjects' => $this->subjects,
            'exams' => $this->exams,
            'examNames' => $this->examNames,
            'className' => $className,
            'termName' => $termName
        ])->setPaper('a4', 'landscape');;
        
        // Notify the user
        Notification::make()
            ->title('PDF is being downloaded')
            ->success()
            ->send();
            
        // Return the PDF for download
        return response()->streamDownload(
            fn () => print($pdf->output()),
            "exam-report-{$className}-{$termName}.pdf"
        );
    }

}