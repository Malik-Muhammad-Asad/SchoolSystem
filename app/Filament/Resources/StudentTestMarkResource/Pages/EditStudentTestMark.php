<?php

namespace App\Filament\Resources\StudentTestMarkResource\Pages;

use App\Filament\Resources\StudentTestMarkResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudentTestMark extends EditRecord
{
    protected static string $resource = StudentTestMarkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
