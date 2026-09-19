<?php

namespace App\Enums;

enum TicketPriority: string
{
    case Relaxed = 'relaxed';
    case Urgent = 'urgent';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Relaxed => 'Santai',
            self::Urgent => 'Mendesak',
            self::Critical => 'Urgent',
        };
    }
}
