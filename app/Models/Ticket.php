<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketTarget;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id', 'ticket_number', 'requester_name', 'whatsapp_number', 'description',
        'priority', 'target', 'status', 'assigned_to', 'updated_by',
        'completed_at', 'admin_note', 'unread_by_user', 'unread_by_supervisor',
    ];

    protected function casts(): array
    {
        return [
            'priority' => TicketPriority::class,
            'status' => TicketStatus::class,
            'completed_at' => 'datetime',
            'unread_by_user' => 'boolean',
            'unread_by_supervisor' => 'boolean',
        ];
    }

    public function targetLabel(): string
    {
        $target = $this->target;

        if ($target instanceof TicketTarget) {
            return $target->label();
        }

        $target = (string) $target;

        return TicketTarget::tryFrom($target)?->label() ?? $target;
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class);
    }
}
