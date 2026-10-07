<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Http\Requests\StoreTicketCommentRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateOwnTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\RoleDashboardSetting;
use App\Models\Ticket;
use App\Services\TelegramService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $ticket = null;

        if ($request->filled('ticket')) {
            $ticket = Ticket::where('ticket_number', strtoupper(trim($request->string('ticket')->toString())))->first();
        }

        return view('welcome', compact('ticket'));
    }

    public function store(StoreTicketRequest $request, TelegramService $telegramService): RedirectResponse
    {
        $validated = $request->validated();
        $uploadedFiles = $request->file('attachments', []);
        unset($validated['attachments']);

        $disk = Storage::disk('local');
        $storedPaths = [];
        $attachmentAttributes = [];

        try {
            foreach ($uploadedFiles as $uploadedFile) {
                $path = $uploadedFile->store('ticket-attachments', 'local');

                if (! is_string($path)) {
                    throw new RuntimeException('Unable to store ticket attachment.');
                }

                $storedPaths[] = $path;
                $attachmentAttributes[] = [
                    'original_name' => $uploadedFile->getClientOriginalName(),
                    'path' => $path,
                    'mime_type' => $uploadedFile->getMimeType() ?? 'application/octet-stream',
                    'size' => $uploadedFile->getSize(),
                ];
            }

            $ticket = DB::transaction(function () use ($request, $validated, $attachmentAttributes): Ticket {
                $ticket = $request->user()->tickets()->create([
                    ...$validated,
                    'ticket_number' => $this->generateTicketNumber(),
                ]);

                $ticket->attachments()->createMany($attachmentAttributes);

                return $ticket;
            });
        } catch (Throwable $exception) {
            foreach ($storedPaths as $storedPath) {
                if ($disk->exists($storedPath) && ! $disk->delete($storedPath)) {
                    report($exception);

                    throw new RuntimeException('Unable to clean up ticket attachment after a failed submission.', previous: $exception);
                }
            }

            throw $exception;
        }

        $telegramService->sendTicketCreatedNotification($ticket);

        return redirect()->route('dashboard')
            ->with('success', 'Pengajuan berhasil dikirim.')
            ->with('created_ticket', $ticket->ticket_number);
    }

    public function dashboard(Request $request): View
    {
        $user = request()->user();
        $roleDashboardSetting = RoleDashboardSetting::query()->firstOrCreate(
            ['role' => $user->role],
            ['features' => RoleDashboardSetting::defaultFeaturesFor($user->role)],
        );
        $featureVisibility = array_merge(
            RoleDashboardSetting::defaultFeaturesFor($user->role),
            $roleDashboardSetting->features ?? [],
        );
        $showCommentTools = $featureVisibility['comment_tools'] ?? false;
        $showEditSubmission = ($featureVisibility['edit_submission'] ?? false) && $user->role === 'user';
        $showActions = ($featureVisibility['actions'] ?? false) && $user->canManageTickets();
        $visibleColumns = [
            'ticket_number' => $featureVisibility['ticket_number'],
            'requester_name' => $featureVisibility['requester_name'],
            'description' => $featureVisibility['description'],
            'whatsapp_number' => $featureVisibility['whatsapp_number'],
            'target' => $featureVisibility['target'],
            'status' => $featureVisibility['status'],
            'new_comment' => $featureVisibility['new_comment'] ?? false,
            'completed_at' => $featureVisibility['completed_at'],
            'comment_tools' => $showCommentTools,
            'edit_submission' => $showEditSubmission,
            'actions' => $showActions,
            'created_at' => $featureVisibility['created_at'],
        ];
        $columnCount = count(array_filter($visibleColumns));
        $baseTicketQuery = $user->role === 'user'
            ? $user->tickets()
            : Ticket::query();
        $ticketQuery = clone $baseTicketQuery;
        $selectedStatus = $request->string('status')->toString();
        $selectedFilter = $request->string('filter')->toString();

        if (in_array($selectedStatus, array_column(TicketStatus::cases(), 'value'), true)) {
            $ticketQuery->where('status', $selectedStatus);
            $selectedFilter = 'status';
        } elseif ($selectedFilter === 'comments' && $showCommentTools) {
            $selectedStatus = 'all';
            $ticketQuery->whereHas('comments', function ($query): void {
                $query->whereHas('user', function ($query): void {
                    $query->whereIn('role', ['owner', 'admin', 'supervisor', 'superadmin']);
                });
            });
        } else {
            $selectedStatus = 'all';
            $selectedFilter = 'all';
        }

        $search = $request->string('search')->trim()->toString();
        if ($search !== '') {
            $ticketQuery->where(function ($query) use ($search): void {
                foreach (['ticket_number', 'requester_name', 'whatsapp_number', 'description'] as $column) {
                    $query->orWhere($column, 'like', '%'.$search.'%');
                }
            });
        }

        $sortableColumns = [
            'created_at',
            'ticket_number',
            'requester_name',
            'whatsapp_number',
            'target',
            'status',
            'completed_at',
        ];
        $sort = $request->string('sort')->toString();
        $sort = in_array($sort, $sortableColumns, true) ? $sort : 'created_at';
        $direction = $request->string('direction')->toString();
        $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : 'desc';

        $tickets = (clone $ticketQuery)
            ->with(['assignee', 'attachments'])
            ->withCount('comments')
            ->orderBy($sort, $direction)
            ->orderByDesc('id')
            ->paginate(15);
        $commentedTicketsQuery = (clone $baseTicketQuery)->whereHas('comments', function ($query): void {
            $query->whereHas('user', function ($query): void {
                $query->whereIn('role', ['owner', 'admin', 'supervisor', 'superadmin']);
            });
        });
        $stats = [
            'total' => (clone $baseTicketQuery)->count(),
            'pending' => (clone $baseTicketQuery)->where('status', TicketStatus::Pending)->count(),
            'in_progress' => (clone $baseTicketQuery)->where('status', TicketStatus::InProgress)->count(),
            'completed' => (clone $baseTicketQuery)->where('status', TicketStatus::Completed)->count(),
            'comments' => $commentedTicketsQuery->count(),
        ];

        return view('dashboard', compact('tickets', 'stats', 'selectedStatus', 'selectedFilter', 'search', 'sort', 'direction', 'featureVisibility', 'showCommentTools', 'showEditSubmission', 'showActions', 'visibleColumns', 'columnCount'));
    }

    public function edit(Ticket $ticket): View
    {
        abort_unless($ticket->user_id === request()->user()->id, 404);

        $ticket->load('attachments');

        return view('tickets.edit', compact('ticket'));
    }

    public function updateOwn(UpdateOwnTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $validated = $request->validated();
        $uploadedFiles = $request->file('attachments', []);
        $removeAttachmentIds = $validated['remove_attachments'] ?? [];
        unset($validated['attachments'], $validated['remove_attachments']);

        $disk = Storage::disk('local');
        $storedPaths = [];
        $attachmentAttributes = [];

        try {
            foreach ($uploadedFiles as $uploadedFile) {
                $path = $uploadedFile->store('ticket-attachments', 'local');

                if (! is_string($path)) {
                    throw new RuntimeException('Unable to store ticket attachment.');
                }

                $storedPaths[] = $path;
                $attachmentAttributes[] = [
                    'original_name' => $uploadedFile->getClientOriginalName(),
                    'path' => $path,
                    'mime_type' => $uploadedFile->getMimeType() ?? 'application/octet-stream',
                    'size' => $uploadedFile->getSize(),
                ];
            }

            $attachmentsToRemove = $ticket->attachments()
                ->whereIn('id', $removeAttachmentIds)
                ->get();

            DB::transaction(function () use ($ticket, $validated, $attachmentAttributes): void {
                $ticket->update($validated);

                if ($attachmentAttributes !== []) {
                    $ticket->attachments()->createMany($attachmentAttributes);
                }
            });
        } catch (Throwable $exception) {
            foreach ($storedPaths as $storedPath) {
                if ($disk->exists($storedPath) && ! $disk->delete($storedPath)) {
                    report($exception);

                    throw new RuntimeException('Unable to clean up ticket attachment after a failed update.', previous: $exception);
                }
            }

            throw $exception;
        }

        foreach ($attachmentsToRemove as $attachment) {
            $attachment->delete();
        }

        return redirect()->route('dashboard')->with('success', "Pengajuan {$ticket->ticket_number} diperbarui.");
    }

    public function comments(Ticket $ticket): View
    {
        $this->authorizeComments($ticket);

        $ticket->load(['comments.user', 'requester']);

        return view('tickets.comments', compact('ticket'));
    }

    public function storeComment(StoreTicketCommentRequest $request, Ticket $ticket): RedirectResponse
    {
        DB::transaction(function () use ($request, $ticket): void {
            $ticket->comments()->create([
                'user_id' => $request->user()->id,
                'body' => $request->validated('body'),
            ]);

            if ($ticket->status === TicketStatus::Completed) {
                $ticket->update([
                    'unread_by_user' => false,
                    'unread_by_supervisor' => false,
                ]);

                return;
            }

            $isUserComment = $request->user()->role === 'user';

            $ticket->update([
                'unread_by_user' => ! $isUserComment,
                'unread_by_supervisor' => $isUserComment,
            ]);
        });

        return redirect()->route('tickets.comments', $ticket)->with('success', 'Pesan berhasil dikirim.');
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $status = TicketStatus::from($request->validated('status'));
        $attributes = [
            ...$request->validated(),
            'status' => $status,
            'updated_by' => $request->user()->id,
            'completed_at' => $status === TicketStatus::Completed ? now() : null,
        ];

        if ($status === TicketStatus::Completed) {
            $attributes['unread_by_user'] = false;
            $attributes['unread_by_supervisor'] = false;
        }

        $ticket->update($attributes);

        return back()->with('success', "Status {$ticket->ticket_number} diperbarui.");
    }

    public function destroy(Ticket $ticket): RedirectResponse
    {
        $ticket->delete();

        return back()->with('success', 'Tiket dihapus dari sistem.');
    }

    private function generateTicketNumber(): string
    {
        do {
            $number = 'INP-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
        } while (Ticket::where('ticket_number', $number)->exists());

        return $number;
    }

    private function authorizeComments(Ticket $ticket): void
    {
        $user = request()->user();

        abort_unless(
            $user !== null && (($user->role === 'user' && $ticket->user_id === $user->id)
                || $user->canManageTickets()),
            403,
        );
    }
}
