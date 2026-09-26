<?php

namespace App\Filament\Pages;

use App\Models\Classes;
use App\Models\ClassSubject;
use App\Models\Exam;
use App\Models\AcademicYear;
use App\Models\StudentTestMark;
use App\Models\Term;
use App\Models\SchoolSetting;
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
    public $previous_term = null;

    // Snapshots set only when Search is clicked (not reactive)
    public $searchedPreviousTerm = null;
    public $searchedExtraExams   = null;
    protected static string $view = 'filament.pages.student-exam-report';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
  protected static ?string $navigationGroup = 'Report';
  protected static ?string $navigationLabel = 'Compilation';
    public function mount()
    {
        $this->form->fill([
            'class'         => null,
            'term'          => null,
            'exams'         => [],
            'ExtraExams'    => null,
            'previous_term' => null,
        ]);
        $this->searchedPreviousTerm = null;
        $this->searchedExtraExams   = null;
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
                        ->options(
                            fn ($get) => $get('term')
                                ? Exam::where('term_id', $get('term'))->pluck('name', 'id')
                                : []
                        )
                        ->placeholder($this->term ? 'Select Exams' : 'Select a Term first')
                        ->required()
                        ->reactive(),

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

                    Forms\Components\Select::make('previous_term')
                        ->label('Previous Term (optional)')
                        ->options(
                            fn ($get) => Term::when(
                                $get('term'),
                                fn ($q, $term) => $q->where('id', '!=', $term)
                            )->pluck('name', 'id')
                        )
                        ->placeholder('None')
                        ->reactive()
                        ->afterStateUpdated(fn ($state) => $this->previous_term = $state),
                ]),
        ];

    }

    public function search()
    {
        // Snapshot filter values — these drive column visibility, not the live reactive props
        $this->searchedPreviousTerm = $this->previous_term;
        $this->searchedExtraExams   = $this->ExtraExams;

        $subjectIds = ClassSubject::where('class_id', $this->class)->pluck('subject_id')->toArray();
        $this->subjects = !empty($subjectIds)
            ? DB::table('subjects')->whereIn('id', $subjectIds)->get()
            : collect([]);

        $students = DB::table('students')
            ->where('is_active', true)
            ->where('class_id', $this->class)
            ->get();

        $results = DB::table('exam_results')
            ->where('class_id', $this->class)
            ->where('term_id', $this->term)
            ->whereIn('exam_id', $this->exams)
            ->get()
            ->groupBy('student_id');

        $this->examNames = Exam::whereIn('id', $this->exams)->pluck('name', 'id')->toArray();

        // ── Pre-calculate current-term max marks (same for all students) ──────
        $examMaxScore = 0;
        foreach ($this->subjects as $subject) {
            $examMaxScore += DB::table('exam_results')
                ->where('class_id', $this->class)
                ->where('subject_id', $subject->id)
                ->where('term_id', $this->term)
                ->whereIn('exam_id', $this->exams)
                ->selectRaw('exam_id, MAX(subject_number) as max_per_exam')
                ->groupBy('exam_id')
                ->get()
                ->sum('max_per_exam');
        }

        // ── Pre-calculate previous-term max marks (same for all students) ─────
        $prevTermMaxScore = 0;
        if ($this->previous_term) {
            $prevTermMaxScore = DB::table('exam_results')
                ->where('class_id', $this->class)
                ->where('term_id', $this->previous_term)
                ->selectRaw('exam_id, subject_id, MAX(subject_number) as max_per_col')
                ->groupBy('exam_id', 'subject_id')
                ->get()
                ->sum('max_per_col');
        }

        // ── Build per-student scores ──────────────────────────────────────────
        $this->scores = $students->map(function ($student) use ($results, $examMaxScore, $prevTermMaxScore) {
            $studentScores  = ['name' => $student->name, 'father_name' => $student->father_name];
            $examTotalScore = 0;

            foreach ($this->subjects as $subject) {
                $examScores        = $this->getExamScores($results->get($student->id), $subject->id);
                $subjectTotalScore = array_sum($examScores);
                $examTotalScore   += $subjectTotalScore;

                $studentScores[$subject->name] = [
                    'exams' => $examScores,
                    'total' => $subjectTotalScore,
                ];
            }

            // Previous term obtained total
            $prevTermTotal = 0;
            if ($this->previous_term) {
                $prevRow = DB::table('exam_results')
                    ->where('class_id', $this->class)
                    ->where('term_id', $this->previous_term)
                    ->where('student_id', $student->id)
                    ->selectRaw('SUM(obtain_number) as total_obtain')
                    ->first();
                $prevTermTotal = $prevRow->total_obtain ?? 0;
            }

            // Extra test marks
            $extraExamScores = $this->ExtraExams
                ? $this->extraNumberObtain($student->id)
                : (object) ['obtain_number' => 0, 'subject_number' => 0];

            $ExtraObtain = $extraExamScores->obtain_number;
            $ExtraMax    = $extraExamScores->subject_number;

            $grandTotal    = $examTotalScore + $prevTermTotal + $ExtraObtain;
            $grandMaxTotal = $examMaxScore   + $prevTermMaxScore + $ExtraMax;

            $percentage = $this->calculatePercentage($grandTotal, $grandMaxTotal);
            $grade      = $this->getGrade($percentage);

            $studentScores['termTotal']      = $examTotalScore;
            $studentScores['prevTermTotal']  = $prevTermTotal;
            $studentScores['ExtraObtain']    = $ExtraObtain;
            $studentScores['total']          = $grandTotal;       // Obtained Grand Total
            $studentScores['grandMaxTotal']  = $grandMaxTotal;    // Total Marks
            $studentScores['percentage']     = $percentage;
            $studentScores['grade']          = $grade;

            return $studentScores;
        });
    }


    private function getExamScores($studentResults, $subjectId)
    {
        return collect($studentResults ?? [])
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
        $schoolSettings = SchoolSetting::first();
        
        // Generate the PDF
        $pdf = PDF::loadView('exports.exam-report', [
            'scores' => $this->scores,
            'subjects' => $this->subjects,
            'exams' => $this->exams,
            'examNames' => $this->examNames,
            'className' => $className,
            'termName' => $termName,
            'searchedPreviousTerm' => $this->searchedPreviousTerm,
            'searchedExtraExams' => $this->searchedExtraExams,
            'schoolSettings' => $schoolSettings,
        ])->setPaper('a4', 'landscape');
        
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