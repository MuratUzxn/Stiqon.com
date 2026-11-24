<?php

namespace App\Enums;

/**
 * Roles inside the admin panel.
 *
 * Owners may change bank, tax, payment and mail settings, manage admins,
 * publish templates, run exports and price imports and switch the shop live.
 * Staff handle orders and content.
 */
enum AdminRole: string
{
    case Owner = 'owner';
    case Staff = 'staff';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Inhaber',
            self::Staff => 'Mitarbeiter',
        };
    }
}
