<?php

namespace App\Filament\Resources\StudentTestMarkResource\Pages;

use App\Filament\Resources\StudentTestMarkResource;
use App\Models\student;
use App\Models\StudentTestMark;
use Filament\Actions\Action;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditStudentTestMark extends EditRecord
{
    protected static string $resource = StudentTestMarkResource::class;

    protected function getFormActions(): array
    {
        return [

            Action::make("Save Changes")->action('saveAndClose'),
            Action::make('Close')
                ->action(function () {
                    return redirect($this->getResource()::getUrl('index')); // Redirect to the index page
                }),
        ];
    }
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $classId = $data['class_id'] ?? null;
        $termId = $data['term_id'] ?? null;
        $TestName = $data['test_name'] ?? null;
        if ($classId && $termId && $TestName) {
            $students = student::where('class_id', $classId)->get();

            $existingTestResults = StudentTestMark::where('class_id', $classId)
                ->where('term_id', $termId)
                ->where('class_id', $classId)
                ->where('test_name', $TestName)
                ->get();

            $testMask = $students->map(function ($student) use ($existingTestResults) {
                $examResult = $existingTestResults->firstWhere('student_id', $student->id);

                return [
                    'student_name' => $student->name,
                    'father_name' => $student->father_name,
                    'student_id' => $student->id,
                    'id' => $examResult->id ?? null,
                    'obtain_number' => $examResult->obtain_number ?? 0,
                ];
            })->toArray();

            $data['test_marks'] = $testMask;
        }

        return $data;
    }

    public function saveAndClose(): void
    {
        $data = $this->form->getState();
        $classId = $data['class_id'];
        $termId = $data['term_id'];
        $testName = $data['test_name'];
        $subjectNumber = $data['subject_number'];
        foreach ($data['test_marks'] as $examResult) {
            StudentTestMark::updateOrCreate(
                [
                    'id' => $examResult['id'],
                    'class_id' => $classId,
                    'term_id' => $termId,
                    'student_id' => $examResult['student_id'], // Student-specific
                ],
                [
                    'test_name' => $testName,
                    'obtain_number' => $examResult['obtain_number'], // Update or create with obtain_number
                    'subject_number' => $subjectNumber,
                ]
            );
        }
        Notification::make()
            ->success()
            ->title('Record Saved')
            ->body('Class subjects have been saved successfully!')
            ->send();

        unset($data['exam_results']);

        redirect($this->getResource()::getUrl('index'));
    }
}
