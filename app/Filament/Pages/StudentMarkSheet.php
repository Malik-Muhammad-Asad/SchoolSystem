<?php

namespace App\Filament\Pages;

use App\Models\Classes;
use App\Models\Exam;
use App\Models\Student;
use App\Models\Term;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms\Components\Grid;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ExamResult;
use App\Helpers\ResultHelper;
use App\Models\FinalResult;
use App\Models\SchoolSetting;

class StudentMarkSheet extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.student-mark-sheet';
    protected static ?string $title = 'Student Mark Sheets';
    protected static ?string $navigationLabel = 'Mark Sheets Report';
    protected static ?string $navigationGroup = 'Report';

    public $class_id = null;
    public $term_id = null;
    public $isSearched = false;
    public $type = "";
    public $previous_term_id = null;


    public function mount(): void
    {
        $this->form->fill([
            'class_id' => $this->class_id,
            'term_id' => $this->term_id,
            'type' => $this->type,
            'previous_term_id' => $this->previous_term_id,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(3)
                    ->schema([
                        Select::make('class_id')
                            ->label('Class')
                            ->options(Classes::pluck('name', 'id')->toArray())
                            ->live()
                            ->required()
                            ->columnSpan(1)
                            ->reactive()
                            ->afterStateUpdated(fn($state) => $this->class_id = $state),

                        Select::make('term_id')
                            ->label('Term')
                            ->options(Term::pluck('name', 'id')->toArray())
                            ->live()
                            ->required()
                            ->columnSpan(1)
                            ->reactive()
                            ->afterStateUpdated(fn($state) => $this->term_id = $state),
                        Select::make('type')
                            ->label('Report Type')
                            ->options([
                                'single' => 'Grand Test',
                                'multi' => 'Half Yearly',
                                'final' => 'Final',
                            ])
                            ->required()
                            ->columnSpan(1)
                            ->reactive()
                            ->afterStateUpdated(fn($state) => $this->type = $state),
                        Select::make('previous_term_id')
                            ->label('Half Yearly Term')
                            ->options(Term::pluck('name', 'id')->toArray())
                            ->placeholder('Select Previous Term')
                            ->visible(fn($get) => $get('type') === 'final')
                            ->required(fn($get) => $get('type') === 'final')
                            ->reactive()
                            ->afterStateUpdated(fn($state) => $this->previous_term_id = $state),
                    ])
                    ->columns(4),
            ]);
    }

    // protected function getHeaderActions(): array
    // {
    //     return [
    //         \Filament\Actions\Action::make('search')
    //             ->label('Search')
    //             ->icon('heroicon-o-magnifying-glass')
    //             ->color('primary')
    //             ->action(fn() => $this->search()),

    //         \Filament\Actions\Action::make('download_all')
    //             ->label('Download All')
    //             ->icon('heroicon-o-document-arrow-down')
    //             ->color('success')
    //             ->visible(fn() => $this->isSearched)
    //             ->action(fn() => $this->downloadAllMarkSheets()),
    //     ];
    // }
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('generate_results')
                ->label('Generate Results')
                ->icon('heroicon-o-calculator')
                ->color('warning')
                ->visible(fn() => $this->class_id && $this->term_id && $this->type)
                ->requiresConfirmation()
                ->modalHeading('Generate Final Results')
                ->modalDescription('This will calculate and lock final results for all students in the selected class and term.')
                ->modalSubmitActionLabel('Generate')
                ->action(fn() => $this->generateResults()),

            \Filament\Actions\Action::make('search')
                ->label('Search')
                ->icon('heroicon-o-magnifying-glass')
                ->color('primary')
                ->action(fn() => $this->search()),

            \Filament\Actions\Action::make('download_all')
                ->label('Download All')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->visible(fn() => $this->isSearched)
                ->action(fn() => $this->downloadAllMarkSheets()),
        ];
    }
    public function generateResults()
    {
        if (!$this->class_id || !$this->term_id) {
            Notification::make()
                ->title('Please select class and term first')
                ->warning()
                ->send();
            return;
        }


        $students = Student::where('class_id', $this->class_id)->get();
        if ($students->isEmpty()) {
            Notification::make()
                ->title('No students found in this class')
                ->warning()
                ->send();
            return;
        }
        $exam_ID = null;

        if ($this->type === 'single') {
            $exam_ID = Exam::where('term_id', $this->term_id)
                ->firstOrFail()
                ->id;
        }

        // Fetch current term results
        $examResults = ExamResult::whereIn('student_id', $students->pluck('id'))
            ->where('term_id', $this->term_id)
            ->when($this->type === 'single', function ($q) use ($exam_ID) {
                $q->where('exam_id', $exam_ID);
            })
            ->get();

        // If 'final', also fetch previous term results
        if ($this->type === 'final' && $this->previous_term_id) {
            $prevTermResults = ExamResult::whereIn('student_id', $students->pluck('id'))
                ->where('term_id', $this->previous_term_id)
                ->get();
            
            // Merge results. ResultHelper will group by subject_id
            $examResults = $examResults->concat($prevTermResults);
        }


        if ($examResults->isEmpty()) {
            Notification::make()
                ->title('No marks found for this term')
                ->warning()
                ->send();
            return;
        }

        foreach ($students as $student) {
            $studentResults = $examResults->where('student_id', $student->id);

            if ($studentResults->isNotEmpty()) {

               
                ResultHelper::calculateAndSave(
                    classID: $this->class_id,
                    studentId: $student->id,
                    examId:  $exam_ID,
                    termId: $this->term_id,
                    subjectResults: $studentResults,
                    reportType: $this->type // Pass the report type
                );
            }
        }


        // Optional: Assign ranks for this term
        $this->assignRanksForTerm($this->term_id, $exam_ID);

        Notification::make()
            ->title('Final results generated successfully!')
            ->success()
            ->send();
    }

    public function search(): void
    {
        $this->validate([
            'class_id' => 'required|exists:class,id',
            'term_id' => 'required|exists:terms,id',
            'type' => 'required',
            'previous_term_id' => 'required_if:type,final'
        ]);

        $this->isSearched = true;
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns($this->getTableColumns())
            ->filters([])
            ->actions($this->getTableActions())
            ->bulkActions($this->getTableBulkActions())
            ->emptyStateActions([]);
    }

    public function getTableQuery(): Builder
    {
        if (!$this->isSearched) {
            return Student::query()->whereRaw('1 = 0'); // Empty query
        }

        return Student::query()->where('class_id', $this->class_id);
    }
    public function assignRanksForTerm($termId, $exam_ID)
    {

        $query = FinalResult::where('term_id', $termId)
            ->where('class_id', $this->class_id)
            ->where('report_type', $this->type)
            ->where('grade', '!=', 'F')
            ->whereHas('student');

        if ($this->type === 'single' && !empty($exam_ID)) {
            $query->where('exam_id', $exam_ID);
        } else {
            $query->where('exam_id', null);
        }

        $results = $query->orderByDesc('percentage')->get();


        $rank = 1;
        foreach ($results as $result) {
            $result->update(['rank' => $rank++]);
        }
    }


    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('serial_no')
                ->label('S.No') // Serial Number Column
                ->state(fn($rowLoop) => $rowLoop->iteration) // Generates serial number
                ->sortable(false),
            // TextColumn::make('id')->label('Roll No')->sortable(),
            TextColumn::make('name')->label('Student Name')->searchable()->sortable(),
            TextColumn::make('father_name')->label('Father Name')->searchable(),
            TextColumn::make('classes.name')->label('Class'),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Action::make('download')
                ->label('Download')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn(Student $record) => route('mark-sheets.download-single', [
                    'student' => $record->id,
                    'term' => $this->term_id,
                    'type' => $this->type,
                    'examID' => Exam::where('term_id', $this->term_id)->first()?->id,
                    'prevTerm' => $this->previous_term_id,
                ]))
                ->openUrlInNewTab(false),
        ];
    }


    public function downloadAllMarkSheets()
    {
        if (!$this->class_id || !$this->term_id) {
            Notification::make()
                ->title('Please select class and term first')
                ->warning()
                ->send();
            return null;
        }

        $students = Student::where('class_id', $this->class_id)->get();
        $exam_ID = Exam::where('term_id', $this->term_id)->value('id');
        $examID = $this->type === "single" ? $exam_ID : null;
        if ($students->isEmpty()) {
            Notification::make()
                ->title('No students found in selected class')
                ->warning()
                ->send();
            return null;
        }
        // $selectionType = $this->form->getState()['selection_type'] ?? 'single';
        // $type = $selectionType === 'single';

        return $this->downloadMarkSheets($students, $this->term_id, $this->type, $examID, $this->previous_term_id);
    }

    public function downloadMarkSheets(Collection $students, $termId, $type, $examID, $prevTermId = null)
    {
        if ($students->isEmpty()) {
            return back()->with('error', 'No students selected.');
        }

        $className = optional($students->first()->classes)->name ?? 'UnknownClass';

        $className = preg_replace('/[^A-Za-z0-9]/', '_', $className);
       
        $schoolSettings = SchoolSetting::first();

        if ($type == "single") {
            $pdf = Pdf::loadView('exports.grandTestMarkSheet', [
                'students' => $students,
                'termId' => $termId,
                'examId' => $examID,
                'schoolSettings' => $schoolSettings,
            ]);
        } elseif ($type == "final") {
            $pdf = Pdf::loadView('exports.finalMarkSheet', [
                'students' => $students,
                'termId' => $termId,
                'prevTermId' => $prevTermId,
                'schoolSettings' => $schoolSettings,
            ]);
        } else {
            $pdf = Pdf::loadView('exports.mark-sheets', [
                'students' => $students,
                'termId' => $termId,
                'schoolSettings' => $schoolSettings,
            ]);
        }

        $fileName = "mark-sheets_{$className}.pdf";

        return response()->streamDownload(fn() => print ($pdf->output()), $fileName);
    }
}
