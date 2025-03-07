<?php

namespace App\Filament\Resources\ClassSubjectResource\Pages;

use App\Filament\Resources\ClassSubjectResource;
use App\Models\ClassSubject;
use Filament\Pages\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewClassSubject extends ViewRecord
{
    protected static string $resource = ClassSubjectResource::class;
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
    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back')
                ->icon('heroicon-o-arrow-left')
                ->url($this->getResource()::getUrl('index'))
        ];
    }
}
