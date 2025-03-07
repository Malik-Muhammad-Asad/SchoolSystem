<?php

namespace App\Filament\Resources\ClassSubjectResource\Pages;

use App\Filament\Resources\ClassSubjectResource;
use App\Models\ClassSubject;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditClassSubject extends EditRecord
{
    protected static string $resource = ClassSubjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back')
                ->icon('heroicon-o-arrow-left')
                ->url($this->getResource()::getUrl('index'))
        ];
    }
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        $assignedSubjects = ClassSubject::where('class_id', $record->class_id)
            ->pluck('subject_id')
            ->toArray();

        $data['class_id'] = $record->class_id;
        $data['subject_id'] = $assignedSubjects;

        return $data;
    }
    protected function mutateFormDataBeforeSave(array $data): array
    {
        ClassSubject::where('class_id', $data['class_id'])->delete();

        foreach ($data['subject_id'] as $subject) {
            ClassSubject::create([
                'class_id' => $data['class_id'],
                'subject_id' => $subject,
            ]);
        }
        $this->notifyAndRedirect();
        $this->halt();
        return $data;
    }
    private function notifyAndRedirect(): void
    {
        Notification::make()
            ->success()
            ->title('Record Updated')
            ->body('Class subjects have been update successfully!')
            ->send();

        $this->redirect($this->getResource()::getUrl('index'));
    }

}
