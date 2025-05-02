<?php

namespace App\Enum;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InputStatus: string implements HasLabel, HasColor
{
    case PAYABLE = 'payable';
    case PAID = 'paid';
    case RETURNED = 'returned';
    case BADLEAD = 'bad lead';
    case NEW = 'new';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PAID => 'paid',
            self::PAYABLE => 'payable',
            self::RETURNED => 'returned',
            self::BADLEAD => 'bad lead',
            self::NEW => 'new',
        };
    }
    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PAYABLE => 'warning',
            self::PAID => 'success',
            self::RETURNED => 'danger',
            self::BADLEAD => 'danger',
            self::NEW => 'primary',
        };
    }
}
