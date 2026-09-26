<?php

namespace App\Filament\Pages;

use App\Models\AcademicYear;
use App\Models\Classes;
use App\Models\ClassSubject;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Student;
use App\Models\Term;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class BulkMarksEntry extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-pencil-square';
    protected static ?string $navigationLabel = 'Bulk Marks Entry';
    protected static ?string $navigationGroup = 'Marks Entry';
    protected static ?string $title           = 'Bulk Marks Entry';
    protected static string  $view            = 'filament.pages.bulk-marks-entry';

    // ── Filter state ──────────────────────────────────────────────
    public ?int   $class_id   = null;
    public array  $term_ids   = [];
    /** Optional student filter – empty means all students */
    public array  $student_ids = [];

    // ── Grid state ────────────────────────────────────────────────
    /** @var bool Whether the grid has been loaded */
    public bool  $isLoaded   = false;

    /**
     * Students list: [['id', 'name', 'father_name'], ...]
     * @var array
     */
    public array $students   = [];

    /**
     * Column definitions: each entry = ['term_name', 'exam_id', 'exam_name', 'subject_id', 'subject_name']
     * @var array
     */
    public array $columns    = [];

    /**
     * Editable max marks per column: ["{examId}_{subjectId}" => value]
     * @var array
     */
    public array $subjectNumbers = [];

    /**
     * The main grid data: ["studentId_examId_subjectId" => obtain_number]
     * Flat map for simple Livewire wire:model binding.
     * @var array
     */
    public array $gridData   = [];

    // ─────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->form->fill([
            'class_id'    => null,
            'term_ids'    => [],
            'student_ids' => [],
        ]);
    }

    public function form(Form $form): Form
    {
        $currentYearId = AcademicYear::where('is_current', true)->value('id');

        return $form->schema([
            Grid::make(3)->schema([
                Select::make('class_id')
                    ->label('Class')
                    ->options(
                        Classes::when($currentYearId, fn ($q) => $q->where('academic_year_id', $currentYearId))
                            ->pluck('name', 'id')
                    )
                    ->placeholder('Select a Class')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->class_id    = $state;
                        $this->student_ids = [];  // reset student filter when class changes
                        $this->isLoaded    = false;
                    }),

                Select::make('term_ids')
                    ->label('Terms')
                    ->options(Term::pluck('name', 'id'))
                    ->placeholder('Select Terms')
                    ->multiple()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->term_ids = $state ?? [];
                        $this->isLoaded = false;
                    }),

                Select::make('student_ids')
                    ->label('Students (optional)')
                    ->placeholder('All students')
                    ->multiple()
                    ->searchable()
                    ->live()
                    ->options(function () {
                        if (!$this->class_id) {
                            return [];
                        }
                        return Student::where('class_id', $this->class_id)
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->pluck('name', 'id');
                    })
                    ->afterStateUpdated(function ($state) {
                        $this->student_ids = $state ?? [];
                        $this->isLoaded    = false;
                    }),
            ]),
        ]);
    }

    // ── Load grid ────────────────────────────────────────────────

    public function loadGrid(): void
    {
        if (!$this->class_id || empty($this->term_ids)) {
            Notification::make()
                ->title('Please select a class and at least one term.')
                ->warning()
                ->send();
            return;
        }

        // ── Students ─────────────────────────────────────────────
        $students = Student::where('class_id', $this->class_id)
            ->where('is_active', true)
            ->when(!empty($this->student_ids), fn ($q) => $q->whereIn('id', $this->student_ids))
            ->orderBy('name')
            ->get(['id', 'name', 'father_name']);

        if ($students->isEmpty()) {
            Notification::make()
                ->title('No active students found in the selected class.')
                ->warning()
                ->send();
            return;
        }

        // ── Subjects for the class ────────────────────────────────
        $subjectIds = ClassSubject::where('class_id', $this->class_id)
            ->pluck('subject_id')
            ->toArray();

        if (empty($subjectIds)) {
            Notification::make()
                ->title('No subjects assigned to this class.')
                ->warning()
                ->send();
            return;
        }

        // ── Exams for the selected terms ──────────────────────────
        $exams = Exam::whereIn('term_id', $this->term_ids)
            ->with('Term')
            ->orderBy('term_id')
            ->orderBy('id')
            ->get();

        if ($exams->isEmpty()) {
            Notification::make()
                ->title('No exams found for the selected terms.')
                ->warning()
                ->send();
            return;
        }

        // ── Subjects (ordered) ────────────────────────────────────
        $subjects = \App\Models\Subject::whereIn('id', $subjectIds)->orderBy('name')->get(['id', 'name']);

        // ── Build column definitions ──────────────────────────────
        $columns = [];
        foreach ($exams as $exam) {
            foreach ($subjects as $subject) {
                $columns[] = [
                    'term_name'    => $exam->Term->name ?? 'Term',
                    'exam_id'      => $exam->id,
                    'exam_name'    => $exam->name,
                    'subject_id'   => $subject->id,
                    'subject_name' => $subject->name,
                    'col_key'      => "{$exam->id}_{$subject->id}",
                ];
            }
        }

        // ── Load existing ExamResults ─────────────────────────────
        $existingResults = ExamResult::where('class_id', $this->class_id)
            ->whereIn('term_id', $this->term_ids)
            ->whereIn('exam_id', $exams->pluck('id'))
            ->whereIn('subject_id', $subjectIds)
            ->get();

        // ── Populate subjectNumbers from existing data ────────────
        $subjectNumbers = [];
        foreach ($existingResults as $r) {
            $key = "{$r->exam_id}_{$r->subject_id}";
            $subjectNumbers[$key] = $r->subject_number;
        }
        // Fill defaults for columns with no existing result
        foreach ($columns as $col) {
            if (!isset($subjectNumbers[$col['col_key']])) {
                $subjectNumbers[$col['col_key']] = 100; // default max
            }
        }

        // ── Populate gridData ─────────────────────────────────────
        $gridData = [];
        foreach ($students as $student) {
            foreach ($columns as $col) {
                $key = "{$student->id}_{$col['exam_id']}_{$col['subject_id']}";
                $existing = $existingResults
                    ->where('student_id', $student->id)
                    ->where('exam_id', $col['exam_id'])
                    ->where('subject_id', $col['subject_id'])
                    ->first();
                $gridData[$key] = $existing ? (string) $existing->obtain_number : '0';
            }
        }

        // ── Assign to properties ──────────────────────────────────
        $this->students       = $students->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'father_name' => $s->father_name])->toArray();
        $this->columns        = $columns;
        $this->subjectNumbers = $subjectNumbers;
        $this->gridData       = $gridData;
        $this->isLoaded       = true;
    }

    // ── Save marks ───────────────────────────────────────────────

    public function saveMarks(): void
    {
        if (!$this->isLoaded) {
            return;
        }

        // Build exam->term map from already-loaded columns to avoid N+1 queries
        $examTermMap = [];
        foreach ($this->columns as $col) {
            $examTermMap[$col['exam_id']] = $col['term_id'] ?? null;
        }

        // Eager-load term_id for each exam (columns may not have term_id, query once)
        $examIds  = array_unique(array_column($this->columns, 'exam_id'));
        $examRows = Exam::withoutGlobalScopes()->whereIn('id', $examIds)->pluck('term_id', 'id');

        $errors = [];

        foreach ($this->students as $student) {
            foreach ($this->columns as $col) {
                $cellKey  = "{$student['id']}_{$col['exam_id']}_{$col['subject_id']}";
                $colKey   = $col['col_key'];
                $obtain   = (float) ($this->gridData[$cellKey] ?? 0);
                $maxMarks = (float) ($this->subjectNumbers[$colKey] ?? 0);

                if ($maxMarks > 0 && $obtain > $maxMarks) {
                    $errors[] = "Student \"{$student['name']}\": obtain ({$obtain}) > max ({$maxMarks}) for {$col['exam_name']} / {$col['subject_name']}";
                    continue;
                }

                $termId = $examRows[$col['exam_id']] ?? null;

                // Use withoutGlobalScopes so the academic-year scope doesn't block matching
                ExamResult::withoutGlobalScopes()->updateOrCreate(
                    [
                        'class_id'   => $this->class_id,
                        'term_id'    => $termId,
                        'exam_id'    => $col['exam_id'],
                        'subject_id' => $col['subject_id'],
                        'student_id' => $student['id'],
                    ],
                    [
                        'obtain_number'  => $obtain,
                        'subject_number' => $maxMarks,
                    ]
                );
            }
        }

        if (!empty($errors)) {
            Notification::make()
                ->title('Some marks were skipped due to validation errors')
                ->body(implode("\n", array_slice($errors, 0, 5)))
                ->warning()
                ->send();
        } else {
            Notification::make()
                ->title('Marks saved successfully!')
                ->success()
                ->send();
        }
    }
}
