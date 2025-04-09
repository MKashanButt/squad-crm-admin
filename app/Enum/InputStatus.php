<?php

namespace App\Enum;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InputStatus: string implements HasLabel, HasColor
{
    case BILLABLE = 'billable';
    case PAID = 'paid';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PAID => 'paid',
            self::BILLABLE => 'billable',
        };
    }
    public function getColor(): string|array|null
    {
        return match ($this) {
            self::BILLABLE => 'orange',
            self::PAID => 'success',
        };
    }
}
