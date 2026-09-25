<?php

namespace App\Enums;

enum TicketTarget: string
{
    case Lppm = 'lppm';
    case Lpm = 'lpm';
    case Ma = 'ma';
    case Trpl = 'trpl';
    case Bk = 'bk';

    public function label(): string
    {
        return match ($this) {
            self::Lppm => 'Web LPPM',
            self::Lpm => 'Web LPM',
            self::Ma => 'Web MA',
            self::Trpl => 'Web TRPL',
            self::Bk => 'Web BK',
        };
    }

    public function userEmail(): string
    {
        return match ($this) {
            self::Lppm => 'user.lppm@example.com',
            self::Lpm => 'user.lpm@example.com',
            self::Ma => 'user.ma@example.com',
            self::Trpl => 'user.trpl@example.com',
            self::Bk => 'user.bk@example.com',
        };
    }
}
