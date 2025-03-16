<?php

namespace App\Filament\Resources\StudentTestMarkResource\Pages;

use App\Models\student;
use App\Models\StudentTestMark;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\StudentTestMarkResource;

class ViewStudentTestMark extends ViewRecord
{
    protected static string $resource = StudentTestMarkResource::class;
    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->icon('heroicon-o-arrow-left')
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

}
