<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class TicketAttachment extends Model
{
    protected $fillable = [
        'original_name',
        'path',
        'mime_type',
        'size',
    ];

    protected static function booted(): void
    {
        static::deleting(function (self $attachment): void {
            $disk = Storage::disk('local');

            if ($disk->exists($attachment->path) && ! $disk->delete($attachment->path)) {
                throw new RuntimeException("Unable to delete ticket attachment [{$attachment->path}].");
            }
        });
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
