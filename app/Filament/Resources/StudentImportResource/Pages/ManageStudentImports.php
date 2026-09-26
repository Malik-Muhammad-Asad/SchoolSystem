<?php

namespace App\Filament\Resources\StudentImportResource\Pages;

use App\Filament\Resources\StudentImportResource;
use App\Models\Student;
use App\Models\StudentImport;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Cell;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;

class ManageStudentImports extends ManageRecords
{
    protected static string $resource = StudentImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download_demo')
                ->label('Download Demo Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $writer = new Writer();
                    $tempPath = storage_path('app/demo_students.xlsx');
                    $writer->openToFile($tempPath);

                    $cells = [
                        Cell::fromValue('name'),
                        Cell::fromValue('father_name'),
                        Cell::fromValue('gr_no'),
                    ];
                    $writer->addRow(new Row($cells));

                    $cells = [
                        Cell::fromValue('John Doe'),
                        Cell::fromValue('Richard Doe'),
                        Cell::fromValue('GR-1001'),
                    ];
                    $writer->addRow(new Row($cells));

                    $writer->close();

                    return response()->download($tempPath, 'student_upload_demo.xlsx')->deleteFileAfterSend(true);
                }),
                
            Actions\CreateAction::make()
                ->label('Upload Students')
                ->icon('heroicon-o-arrow-up-tray')
                ->using(function (array $data, string $model): StudentImport {
                    $classId = $data['class_id'];
                    $filePath = Storage::disk('local')->path($data['excel_file']);
                    
                    $totalCount = 0;
                    $failedCount = 0;
                    $failedData = [];

                    $reader = new Reader();
                    try {
                        $reader->open($filePath);
                        $headerMap = [];

                        foreach ($reader->getSheetIterator() as $sheet) {
                            foreach ($sheet->getRowIterator() as $index => $row) {
                                $cells = $row->getCells();
                                
                                if ($index === 1) {
                                    // Header row mapping
                                    foreach ($cells as $cellIndex => $cell) {
                                        $headerMap[strtolower(trim($cell->getValue()))] = $cellIndex;
                                    }
                                    
                                    if (!isset($headerMap['name']) || !isset($headerMap['father_name'])) {
                                        throw new \Exception('The Excel file must have "name" and "father_name" columns.');
                                    }
                                    continue;
                                }

                                $totalCount++;
                                $name = '';
                                $fatherName = '';
                                $grNo = null;

                                if (isset($headerMap['name']) && isset($cells[$headerMap['name']])) {
                                    $name = $cells[$headerMap['name']]->getValue();
                                }
                                if (isset($headerMap['father_name']) && isset($cells[$headerMap['father_name']])) {
                                    $fatherName = $cells[$headerMap['father_name']]->getValue();
                                }
                                if (isset($headerMap['gr_no']) && isset($cells[$headerMap['gr_no']])) {
                                    $grNo = $cells[$headerMap['gr_no']]->getValue();
                                }

                                if (empty($name) || empty($fatherName)) {
                                    $failedCount++;
                                    $failedData[] = ['name' => $name, 'father_name' => $fatherName, 'gr_no' => $grNo, 'status' => 'Missing Required Data'];
                                    continue;
                                }

                                if ($grNo && Student::where('gr_no', $grNo)->exists()) {
                                    $failedCount++;
                                    $failedData[] = ['name' => $name, 'father_name' => $fatherName, 'gr_no' => $grNo, 'status' => 'Duplicate GR No'];
                                    continue;
                                }

                                try {
                                    Student::create([
                                        'name' => $name,
                                        'father_name' => $fatherName,
                                        'gr_no' => $grNo,
                                        'class_id' => $classId,
                                        'is_active' => true,
                                    ]);
                                } catch (\Exception $e) {
                                    $failedCount++;
                                    $failedData[] = ['name' => $name, 'father_name' => $fatherName, 'gr_no' => $grNo, 'status' => $e->getMessage()];
                                }
                            }
                            break; 
                        }
                        $reader->close();

                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Import Failed completely')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                        
                        // Still record it as completely failed
                    }
                    
                    // Retrieve the actual uploaded file instance to get the original name
                    // In Filament, file uploads are stored in Livewire's temporary upload directory first,
                    // and $data['excel_file'] contains the path. We can get the original name by finding the Livewire file object,
                    // or storing the file using a custom name earlier. However, the simplest way since it's already saved locally is grabbing 
                    // the original filename from the form component state if available, but the string in $data['excel_file'] is the *new* hash.
                    
                    // To keep it simple, we will use a common technique for Filament file uploads:
                    // Filament saves the original file name in the nested Livewire logic, but we can just use the hash as the true path
                    // and extract the original file name. Actually, $this->form->getRawState()['excel_file'] might be needed, but since we are in an Action closure,
                    // we don't have direct access to $this. Let's use the file name of the hash for now, but to get original name properly we should update the form definition to keep original name.
                    // Wait, Filament v3 FileUpload provides `getUploadedFileNameForStorageUsing` to preserve original names, or we can just access it.
                    // A quick fix without re-writing the form is just using the disk's original name if possible, or we just leave basename.
                    // Actually, a better fix is modifying the FileUpload field to store original name.
                    
                    // Let's at least ensure we don't just show a massive hash.
                    // We will update the FileUpload field in the Resource to use original names for the view!
                    
                    // For now, we will save the generic name here, but let's actually fix this in the form definition.
                    return $model::create([
                        'class_id' => $classId,
                        'file_name' => basename($data['excel_file']), // We will fix the actual file creation in StudentImportResource.php
                        'total_records' => $totalCount,
                        'failed_records' => $failedCount,
                        'failed_data' => count($failedData) > 0 ? $failedData : null,
                    ]);
                })
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Upload processing completed')
                        ->body('Check the import list for the results and any failed records.')
                ),
        ];
    }
}
