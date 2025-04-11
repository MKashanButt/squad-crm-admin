<?php

namespace App\Filament\Resources\BadLeadsResource\Pages;

use App\Filament\Resources\BadLeadsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBadLeads extends EditRecord
{
    protected static string $resource = BadLeadsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
