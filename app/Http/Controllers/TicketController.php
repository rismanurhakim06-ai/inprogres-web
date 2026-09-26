<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Enums\TicketTarget;
use App\Http\Requests\StoreTicketCommentRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateOwnTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\RoleDashboardSetting;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

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

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $ticket = $request->user()->tickets()->create([
            ...$request->validated(),
            'ticket_number' => $this->generateTicketNumber(),
        ]);

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
        $showCommentTools = ($featureVisibility['comment_tools'] ?? false) && $user->role !== 'supervisor';
        $showEditSubmission = ($featureVisibility['edit_submission'] ?? false) && $user->role === 'user';
        $showActions = ($featureVisibility['actions'] ?? false) && $user->canManageTickets();
        $visibleColumns = [
            'ticket_number' => $featureVisibility['ticket_number'],
            'requester_name' => $featureVisibility['requester_name'],
            'description' => $featureVisibility['description'],
            'whatsapp_number' => $featureVisibility['whatsapp_number'],
            'target' => $featureVisibility['target'],
            'status' => $featureVisibility['status'],
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
                    $query->whereIn('role', ['owner', 'admin', 'superadmin']);
                });
            });
        } else {
            $selectedStatus = 'all';
            $selectedFilter = 'all';
        }

        $tickets = (clone $ticketQuery)->with('assignee')->withCount('comments')->latest()->paginate(15);
        $commentedTicketsQuery = (clone $baseTicketQuery)->whereHas('comments', function ($query): void {
            $query->whereHas('user', function ($query): void {
                $query->whereIn('role', ['owner', 'admin', 'superadmin']);
            });
        });
        $stats = [
            'total' => (clone $baseTicketQuery)->count(),
            'pending' => (clone $baseTicketQuery)->where('status', TicketStatus::Pending)->count(),
            'in_progress' => (clone $baseTicketQuery)->where('status', TicketStatus::InProgress)->count(),
            'completed' => (clone $baseTicketQuery)->where('status', TicketStatus::Completed)->count(),
            'comments' => $commentedTicketsQuery->count(),
        ];

        return view('dashboard', compact('tickets', 'stats', 'selectedStatus', 'selectedFilter', 'featureVisibility', 'showCommentTools', 'showEditSubmission', 'showActions', 'visibleColumns', 'columnCount'));
    }

    public function edit(Ticket $ticket): View
    {
        return view('tickets.edit', compact('ticket'));
    }

    public function updateOwn(UpdateOwnTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $validated = $request->validated();
        $target = TicketTarget::from($validated['target']);
        $targetUser = User::where('role', 'user')
            ->where('target', $target->value)
            ->firstOrFail();

        $ticket->update([
            ...$validated,
            'user_id' => $targetUser->id,
        ]);

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
        TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        return redirect()->route('tickets.comments', $ticket)->with('success', 'Pesan berhasil dikirim.');
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $status = TicketStatus::from($request->validated('status'));
        $ticket->update([
            ...$request->validated(),
            'status' => $status,
            'updated_by' => $request->user()->id,
            'completed_at' => $status === TicketStatus::Completed ? now() : null,
        ]);

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
                || in_array($user->role, ['owner', 'admin', 'superadmin'], true)),
            403,
        );
    }
}
