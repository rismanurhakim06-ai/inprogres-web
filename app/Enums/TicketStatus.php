<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Belum disetujui',
            self::InProgress => 'Sedang dikerjakan',
            self::Completed => 'Selesai',
            self::Rejected => 'Ditolak',
        };
    }
}
