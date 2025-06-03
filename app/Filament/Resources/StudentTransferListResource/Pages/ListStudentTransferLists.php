<?php

namespace App\Filament\Resources\StudentTransferListResource\Pages;

use App\Filament\Resources\StudentTransferListResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStudentTransferLists extends ListRecords
{
    protected static string $resource = StudentTransferListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
