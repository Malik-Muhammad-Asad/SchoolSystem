<?php

namespace App\Filament\Resources\ClassSubjectResource\Pages;

use App\Filament\Resources\ClassSubjectResource;
use App\Models\ClassSubject;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateClassSubject extends CreateRecord
{
    protected static string $resource = ClassSubjectResource::class;

    public function mutateFormDataBeforeCreate(array $data): array
    {
        ClassSubject::where('class_id', $data['class_id'])->delete();
        $subjects = $data['subject_id'];

        foreach ($subjects as $subject) {
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
            ->title('Record Saved')
            ->body('Class subjects have been saved successfully!')
            ->send();

        $this->redirect($this->getResource()::getUrl('index'));
    }


}
