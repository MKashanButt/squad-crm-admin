<?php

namespace App\Filament\Resources\DeductedReturnResource\Pages;

use App\Filament\Resources\DeductedReturnResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDeductedReturns extends ListRecords
{
    protected static string $resource = DeductedReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
