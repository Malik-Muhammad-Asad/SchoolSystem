<?php

namespace App\Filament\Resources\ExamResultResource\Pages;

use App\Filament\Resources\ExamResultResource;
use App\Models\ExamResult;
use Filament\Actions;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateExamResult extends CreateRecord
{
    protected static string $resource = ExamResultResource::class;

    // protected function mutateFormDataBeforeCreate(array $data): array
    // {
    //     $examResults = collect($data['exam_results']);
    //     $examResults->each(function ($result) use ($data) {

    //         \App\Models\ExamResult::create([
    //             'class_id' => $data['class_id'],
    //             'term_id' => $data['term_id'],
    //             'exam_id' => $data['exam_id'],
    //             'subject_id' => $data['subject_id'],
    //             'student_id' => $result['student_id'],
    //             'subject_number' => $data['subject_number'],
    //             'obtain_number' => $result['obtain_number'],
    //         ]);
    //     });
    //     Notification::make()
    //         ->success()
    //         ->title('Record Created')
    //         ->body('The record has been created successfully!')
    //         ->send();

    //     return $data;
    // }
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $examResults = collect($data['exam_results']);

        $examResults->each(function ($result) use ($data) {
            $existingExamResult = ExamResult::where('class_id', $data['class_id'])
                ->where('term_id', $data['term_id'])
                ->where('exam_id', $data['exam_id'])
                ->where('subject_id', $data['subject_id'])
                ->where('student_id', $result['student_id'])
                ->first();

            if ($existingExamResult) {
                // If the record exists, update it
                $existingExamResult->update([
                    'subject_number' => $data['subject_number'],
                    'obtain_number' => $result['obtain_number'],
                ]);
            } else {
                // If it doesn't exist, create a new one
                ExamResult::create([
                    'class_id' => $data['class_id'],
                    'term_id' => $data['term_id'],
                    'exam_id' => $data['exam_id'],
                    'subject_id' => $data['subject_id'],
                    'student_id' => $result['student_id'],
                    'subject_number' => $data['subject_number'],
                    'obtain_number' => $result['obtain_number'],
                ]);
            }
        });

        // Success notification
        Notification::make()
            ->success()
            ->title('Record Saved')
            ->body('Exam results have been saved successfully!')
            ->send();

        return $data;
    }
    protected function afterCreate()
    {
        $taskDetail = $this->record;
        $taskDetail->delete();
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

}
