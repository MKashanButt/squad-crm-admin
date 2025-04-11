<?php

namespace App\Enum;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InputStatus: string implements HasLabel, HasColor
{
    case BILLABLE = 'billable';
    case PAID = 'paid';
    case RETURNED = 'returned';

    case BADLEAD = 'bad lead';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PAID => 'paid',
            self::BILLABLE => 'billable',
            self::RETURNED => 'returned',
            self::BADLEAD => 'bad lead',
        };
    }
    public function getColor(): string|array|null
    {
        return match ($this) {
            self::BILLABLE => 'warning',
            self::PAID => 'success',
            self::RETURNED => 'danger',
            self::BADLEAD => 'primary',
        };
    }
}
