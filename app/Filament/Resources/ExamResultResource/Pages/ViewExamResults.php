<?php

namespace App\Filament\Resources\ExamResultResource\Pages;

use App\Filament\Resources\ExamResultResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewExamResults extends ViewRecord
{
    protected static string $resource = ExamResultResource::class;


    protected function mutateFormDataBeforeFill(array $data): array
    {
       

        $classId = $data['class_id'] ?? null;
        $termId = $data['term_id'] ?? null;
        $examId = $data['exam_id'] ?? null;
        $subjectId = $data['subject_id'] ?? null;

        if ($classId && $termId && $examId && $subjectId) {
           

            // Fetch existing exam results for the specified class, term, exam, and subject
            $existingExamResults = \App\Models\ExamResult::where('class_id', $classId)
                ->where('term_id', $termId)
                ->where('exam_id', $examId)
                ->where('subject_id', $subjectId)
                ->get();
                $examResults = $existingExamResults->map(function ($examResult) {
                    // Return the necessary fields for the repeater
                    return [
                        'student_id' => $examResult->student_id,
                        'student_name' => $examResult->student->name, 
                        'father_name' => $examResult->student->father_name, 
                        'obtain_number' => $examResult->obtain_number,
                    ];
                })->toArray();
            
            $data['exam_results'] = $examResults;
        }
     
        return $data;
    }
    protected function getHeaderActions(): array
    {
        return [
           
        ];
    }
}
