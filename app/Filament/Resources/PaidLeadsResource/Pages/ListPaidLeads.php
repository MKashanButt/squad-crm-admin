<?php

namespace App\Filament\Resources\PaidLeadsResource\Pages;

use App\Filament\Resources\PaidLeadsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPaidLeads extends ListRecords
{
    protected static string $resource = PaidLeadsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
