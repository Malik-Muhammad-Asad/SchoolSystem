<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentImportResource\Pages;
use App\Models\StudentImport;
use App\Models\Classes;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Cell;
use Illuminate\Support\Facades\Storage;

class StudentImportResource extends Resource
{
    protected static ?string $model = StudentImport::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Student Management';
    
    protected static ?string $navigationLabel = 'Upload Students';

    protected static ?string $pluralLabel = 'Student Uploads (History)';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('class_id')
                    ->label('Class')
                    ->options(Classes::pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                FileUpload::make('excel_file')
                    ->label('Excel File')
                    ->disk('local')
                    ->directory('student-imports')
                    ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                    ->preserveFilenames()
                    ->required()
                    // Keep the file temporarily in the form, do not try to save it directly to the model
                    ->saveUploadedFileUsing(fn ($file) => $file->storeAs('student-imports', $file->getClientOriginalName(), 'local')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('classes.name')
                    ->label('Class')
                    ->sortable(),
                Tables\Columns\TextColumn::make('file_name')
                    ->label('File Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_records')
                    ->label('Total Records')
                    ->sortable(),
                Tables\Columns\TextColumn::make('failed_records')
                    ->label('Failed Records')
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('download_errors')
                    ->label('Download Errors')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('danger')
                    ->visible(fn (StudentImport $record) => $record->failed_records > 0 && !empty($record->failed_data))
                    ->action(function (StudentImport $record) {
                        $writer = new Writer();
                        $tempPath = storage_path('app/failed_imports_' . $record->id . '.xlsx');
                        $writer->openToFile($tempPath);

                        // Define headers
                        $cells = [
                            Cell::fromValue('Name'),
                            Cell::fromValue('Father Name'),
                            Cell::fromValue('GR No'),
                            Cell::fromValue('Error Reason'),
                        ];
                        $writer->addRow(new Row($cells));

                        // Add failed rows
                        foreach ($record->failed_data as $failedRow) {
                            $cells = [
                                Cell::fromValue($failedRow['name'] ?? ''),
                                Cell::fromValue($failedRow['father_name'] ?? ''),
                                Cell::fromValue($failedRow['gr_no'] ?? ''),
                                Cell::fromValue($failedRow['status'] ?? ''),
                            ];
                            $writer->addRow(new Row($cells));
                        }

                        $writer->close();

                        return response()->download($tempPath, 'Failed_Students_Import.xlsx')->deleteFileAfterSend(true);
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageStudentImports::route('/'),
        ];
    }
}
