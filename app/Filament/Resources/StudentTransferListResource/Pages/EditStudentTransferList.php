<?php

namespace App\Filament\Resources\StudentTransferListResource\Pages;

use App\Filament\Resources\StudentTransferListResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudentTransferList extends EditRecord
{
    protected static string $resource = StudentTransferListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
