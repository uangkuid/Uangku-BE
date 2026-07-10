<?php

namespace App\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Banned = 'banned';

    /** Status yang membuat user tidak boleh mengakses API. */
    public function isBlocked(): bool
    {
        return $this === self::Suspended || $this === self::Banned;
    }

    /** Label untuk UI admin. */
    public function label(): string
    {
        return __('staffsus/enums.user_status.'.$this->value);
    }

    /** Warna badge Filament. */
    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Suspended => 'warning',
            self::Banned => 'danger',
        };
    }
}
