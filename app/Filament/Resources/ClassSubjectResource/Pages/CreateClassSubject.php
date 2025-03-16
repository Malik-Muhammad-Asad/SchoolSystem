<?php

namespace App\Filament\Resources\ClassSubjectResource\Pages;

use App\Filament\Resources\ClassSubjectResource;
use App\Models\ClassSubject;
use Filament\Notifications\Notification;

use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;


class CreateClassSubject extends CreateRecord
{
    
    protected static string $resource = ClassSubjectResource::class;
    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(), 
            Action::make('back')
            ->label('Back')
            ->icon('heroicon-o-arrow-left')
            ->url($this->getResource()::getUrl('index'))// Sirf "Create" button dikhai dega
        ];
    }
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
