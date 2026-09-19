<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketTarget;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    protected $fillable = [
        'ticket_number', 'requester_name', 'whatsapp_number', 'description',
        'priority', 'target', 'status', 'assigned_to', 'updated_by',
        'completed_at', 'admin_note',
    ];

    protected function casts(): array
    {
        return [
            'priority' => TicketPriority::class,
            'target' => TicketTarget::class,
            'status' => TicketStatus::class,
            'completed_at' => 'datetime',
        ];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
