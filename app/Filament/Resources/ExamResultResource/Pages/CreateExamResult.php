<?php

namespace App\Filament\Resources\ExamResultResource\Pages;

use App\Filament\Resources\ExamResultResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateExamResult extends CreateRecord
{
    protected static string $resource = ExamResultResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $examResults = collect($data['exam_results']);

        $examResults->each(function ($result) use ($data) {

            \App\Models\ExamResult::create([
                'class_id' => $data['class_id'],
                'term_id' => $data['term_id'],
                'exam_id' => $data['exam_id'],
                'subject_id' => $data['subject_id'],
                'student_id' => $result['student_id'],
                'subject_number' => $data['subject_number'],
                'obtain_number' => $result['obtain_number'],
            ]);
        });
        Notification::make()
            ->success()
            ->title('Record Created')
            ->body('The record has been created successfully!')
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
