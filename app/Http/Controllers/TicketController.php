<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Ticket;
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
        $ticket = Ticket::create([
            ...$request->validated(),
            'ticket_number' => $this->generateTicketNumber(),
        ]);

        return redirect()->route('home')
            ->with('success', 'Pengajuan berhasil dikirim.')
            ->with('created_ticket', $ticket->ticket_number);
    }

    public function dashboard(): View
    {
        $tickets = Ticket::with('assignee')->latest()->paginate(15);
        $stats = [
            'total' => Ticket::count(),
            'pending' => Ticket::where('status', TicketStatus::Pending)->count(),
            'in_progress' => Ticket::where('status', TicketStatus::InProgress)->count(),
            'completed' => Ticket::where('status', TicketStatus::Completed)->count(),
        ];

        return view('dashboard', compact('tickets', 'stats'));
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
}
