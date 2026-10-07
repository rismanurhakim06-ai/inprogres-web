<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketAttachmentController extends Controller
{
    public function show(Request $request, Ticket $ticket, TicketAttachment $attachment): StreamedResponse
    {
        abort_unless(
            $ticket->user_id === $request->user()->id || $request->user()->canManageTickets(),
            404,
        );

        $attachment = $ticket->attachments()->findOrFail($attachment->getKey());
        $disk = Storage::disk('local');

        abort_unless($disk->exists($attachment->path), 404);

        $isPreviewableImage = in_array($attachment->mime_type, [
            'image/jpeg',
            'image/png',
            'image/webp',
        ], true);

        return $disk->response(
            $attachment->path,
            $attachment->original_name,
            [
                'Content-Type' => $attachment->mime_type,
                'X-Content-Type-Options' => 'nosniff',
            ],
            $isPreviewableImage ? 'inline' : 'attachment',
        );
    }
}
