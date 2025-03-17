<?php

namespace App\Filament\Pages;

use App\Models\Classes;
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

class StudentMarkSheet extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.student-mark-sheet';
    protected static ?string $title = 'Student Mark Sheets';
    protected static ?string $navigationLabel = 'Mark Sheets';

    public $class_id = null;
    public $term_id = null;
    public $isSearched = false;

    public function mount(): void
    {
        $this->form->fill([
            'class_id' => $this->class_id,
            'term_id' => $this->term_id,
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
                            ->options(Classes::pluck('name', 'id')->toArray()) // Ensure array format
                            ->live()
                            ->required()
                            ->columnSpan(1)
                            ->reactive()
                            ->afterStateUpdated(fn($state) => $this->class_id = $state),

                        Select::make('term_id')
                            ->label('Term')
                            ->options(Term::pluck('name', 'id')->toArray()) // Ensure array format
                            ->live()
                            ->required()
                            ->columnSpan(1)
                            ->reactive()
                            ->afterStateUpdated(fn($state) => $this->term_id = $state),
                    ])
                    ->columns(3),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('search')
                ->label('Search')
                ->icon('heroicon-o-magnifying-glass')
                ->color('primary')
                ->action(fn() => $this->search()),
        ];
    }

    public function search(): void
    {
        $this->validate([
            'class_id' => 'required|exists:class,id',
            'term_id' => 'required|exists:terms,id',
        ]);

        $this->isSearched = true;
        $this->resetTable();
    }

    public function getTableQuery(): Builder
    {
        if (!$this->isSearched) {
            return Student::query()->whereRaw('1 = 0'); // Empty query
        }

        return Student::query()->where('class_id', $this->class_id);
    }


    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('id')->label('Roll No')->sortable(),
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
                ->form([
                    Select::make('term_id')
                        ->label('Term')
                        ->options(Term::pluck('name', 'id')->toArray()) // Ensure array format
                        ->default($this->term_id)
                        ->required(),
                ])
                ->action(
                    fn(Student $record, array $data) =>
                    redirect()->route('mark-sheets.download-single', [
                        'student' => $record->id,
                        'term' => $data['term_id'],
                    ])
                ),
        ];
    }

    protected function isTableBulkActionsEnabled(): bool
    {
        return true;
    }

    protected function getTableBulkActions(): array
    {
        return [
            BulkAction::make('download')
                ->label('Download Mark Sheets')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Download Selected Student Mark Sheets')
                ->modalDescription('Are you sure you want to download mark sheets for selected students?')
                ->modalSubmitActionLabel('Yes, Download')
                ->form([
                    Select::make('term_id')
                        ->label('Term')
                        ->options(Term::pluck('name', 'id')->toArray())
                        ->default($this->term_id)
                        ->required(),
                ])
                ->action(function (Collection $records, array $data) {
                    if ($records->isEmpty()) {
                        Notification::make()
                            ->title('No students selected')
                            ->warning()
                            ->send();
                        return null;
                    }

                    return $this->downloadMarkSheets($records, $data['term_id']);
                })
                ->deselectRecordsAfterCompletion(),
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

        if ($students->isEmpty()) {
            Notification::make()
                ->title('No students found in selected class')
                ->warning()
                ->send();
            return null;
        }

        return $this->downloadMarkSheets($students, $this->term_id);
    }



    // public function downloadMarkSheets(Collection $students, $termId)
    // {
    //     $pdf = Pdf::loadView('exports.mark-sheets', [
    //         'students' => $students,
    //         'termId' => $termId,
    //     ]);

    //     return response()->streamDownload(fn() => print ($pdf->output()), 'mark-sheets.pdf');
    // }
}
