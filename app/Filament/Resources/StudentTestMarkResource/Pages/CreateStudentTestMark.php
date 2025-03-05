<?php
namespace App\Filament\Resources\StudentTestMarkResource\Pages;

use App\Filament\Resources\StudentTestMarkResource;
use App\Models\StudentTestMark;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Filament\Resources\Pages\CreateRecord;

class CreateStudentTestMark extends CreateRecord
{
    protected static string $resource = StudentTestMarkResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $students = $data['test_marks'] ?? []; // Ensure we get the repeater data

        if (empty($students)) {
            throw new \Exception("No students found! Please select a class with students.");
        }

        DB::transaction(function () use ($students, $data) {
            foreach ($students as $student) {
                StudentTestMark::create([
                    'student_id' => $student['student_id'],
                    'class_id' => $data['class_id'] ?? null,
                    'subject_id' => $data['subject_id'] ?? null,
                    'term_id' => $data['term_id'] ?? null,
                    'obtain_number' => $student['obtain_number'] ?? 0,
                    'subject_number' => $data['subject_number'] ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        Notification::make()
            ->success()
            ->title('Record Saved')
            ->body('Test results have been saved successfully!')
            ->send();

        // ✅ Correct way to redirect in Filament Livewire:
        $this->redirect(StudentTestMarkResource::getUrl('index'));

        return $data; // ✅ Ensure function returns an array
    }


}
