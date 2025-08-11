<?php

namespace App\Filament\Resources\DeductedReturnResource\Pages;

use App\Filament\Resources\DeductedReturnResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDeductedReturn extends EditRecord
{
    protected static string $resource = DeductedReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
