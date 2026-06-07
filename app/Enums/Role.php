<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Membership roles within a neighbourhood (PRD § Access Control).
 *
 * Defined here as a type for F-03 to attach to the Membership model; F-02 does
 * not assign or read it yet.
 */
enum Role: string
{
    case Founder = 'founder';
    case Owner = 'owner';
    case Guest = 'guest';
    case Pending = 'pending';

    /** Polish display name shown in the UI. */
    public function label(): string
    {
        return match ($this) {
            self::Founder => 'Założyciel',
            self::Owner => 'Właściciel',
            self::Guest => 'Gość',
            self::Pending => 'Oczekujący',
        };
    }
}
