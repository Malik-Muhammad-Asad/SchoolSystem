<?php

namespace App\Filament\Resources\ExamResultResource\Pages;

use App\Filament\Resources\ExamResultResource;
use App\Models\student;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\EditRecord;

class EditExamResult extends EditRecord
{
    protected static string $resource = ExamResultResource::class;

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
        // Fetch necessary IDs from $data
        $classId = $data['class_id'] ?? null;
        $termId = $data['term_id'] ?? null;
        $examId = $data['exam_id'] ?? null;
        $subjectId = $data['subject_id'] ?? null;

        if ($classId && $termId && $examId && $subjectId) {
            $students = student::where('class_id', $classId)->get();

            // Fetch existing exam results for the specified class, term, exam, and subject
            $existingExamResults = \App\Models\ExamResult::where('class_id', $classId)
                ->where('term_id', $termId)
                ->where('exam_id', $examId)
                ->where('subject_id', $subjectId)
                ->get();

            // Map students to the repeater data
            $examResults = $students->map(function ($student) use ($existingExamResults) {
                $examResult = $existingExamResults->firstWhere('student_id', $student->id);

                return [
                    'student_name' => $student->name,
                    'father_name' => $student->father_name,
                    'student_id' => $student->id,
                    'obtain_number' => $examResult->obtain_number ?? 0, // Default to 0 if no result exists
                ];
            })->toArray();

            // Add the exam results to the data array
            $data['exam_results'] = $examResults;
        }

        return $data;
    }
    public function saveAndClose(): void
    {
        $data = $this->form->getState();
        $classId = $data['class_id'];
        $termId = $data['term_id'];
        $examId = $data['exam_id'];
        $subjectId = $data['subject_id'];
        $subjectNumber = $data['subject_number'];

        // Loop through the exam_results and update or create the exam records
        foreach ($data['exam_results'] as $examResult) {
            \App\Models\ExamResult::updateOrCreate(
                [
                    'class_id' => $classId,
                    'term_id' => $termId,
                    'exam_id' => $examId,
                    'subject_id' => $subjectId,
                    'student_id' => $examResult['student_id'], // Student-specific
                ],
                [
                    'obtain_number' => $examResult['obtain_number'], // Update or create with obtain_number
                    'subject_number' => $subjectNumber, // Set the subject_number
                ]
            );
        }

        // Clean up the data (remove exam_results field as it's not needed for saving to the main table)
        unset($data['exam_results']);

        redirect($this->getResource()::getUrl('index'));
    }




}
