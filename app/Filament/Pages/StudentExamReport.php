<?php
namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class StudentExamReport extends Page implements HasTable
{
    use InteractsWithTable;

    public $classId, $termId, $examIds = [];
    public $sheetData = [];

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.student-exam-report';

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student')
                    ->label('Student Name')
                    ->sortable(),

                // Dynamically add subject columns
                Tables\Columns\ViewColumn::make('marks')
                    ->label('Marks')
                    ->view('components.subject-marks-table'), // Custom blade component to render marks
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('class')
                    ->label('Class')
                    ->options([
                        1 => 'Class 1',
                        2 => 'Class 2',
                        3 => 'Class 3',
                    ]),
                Tables\Filters\SelectFilter::make('term')
                    ->label('Term')
                    ->options([
                        1 => 'Term 1',
                        2 => 'Term 2',
                        3 => 'Term 3',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_pdf')
                    ->label('Export to PDF')
                    ->action('exportPDF'),
            ]);
    }

    public function updatedFilters()
    {
        $this->fetchData();
    }

    // Define the query method to fetch data
    public function query()
    {
        if (!$this->classId || !$this->termId || empty($this->examIds)) {
            return collect(); // Return an empty collection if filters are not set
        }

        // Query data from the database
        return DB::table('examresult')
            ->join('students', 'examresult.student_id', '=', 'students.id')
            ->join('subjects', 'examresult.subject_id', '=', 'subjects.id')
            ->where('examresult.class_id', $this->classId)
            ->where('examresult.term_id', $this->termId)
            ->whereIn('examresult.exam_id', $this->examIds)
            ->select('examresult.student_id', 'students.name', 'subjects.name as subject', 'examresult.obtain_number', 'examresult.exam_id', 'examresult.subject_id')
            ->get();
    }

    // Export data to PDF
    public function exportPDF()
    {
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('exports.student-exam-report', ['sheetData' => $this->sheetData]);
        return $pdf->download('student-exam-report.pdf');
    }
}
