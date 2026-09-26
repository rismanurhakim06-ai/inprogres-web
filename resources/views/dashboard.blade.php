<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-600">Workspace operasional</p>
                <h2 class="font-semibold text-2xl text-gray-900">Daftar pengajuan</h2>
            </div>
            <span class="rounded-full bg-gray-100 px-3 py-1 text-sm text-gray-600">{{ auth()->user()->role }}</span>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8" x-data="{ showDescription: false, description: '', showNewTicketForm: @js($errors->any()) }">
        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @if (session('created_ticket'))
            <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">Nomor tiket baru: <strong>{{ session('created_ticket') }}</strong></div>
        @endif

        <div class="flex flex-nowrap gap-4 overflow-x-auto pb-1">
            @foreach ([['total', 'Total tiket', 'all'], ['pending', 'Menunggu', 'pending'], ['in_progress', 'Dikerjakan', 'in_progress'], ['completed', 'Selesai', 'completed']] as [$key, $label, $filter])
            <a href="{{ route('dashboard', $filter === 'all' ? [] : ['status' => $filter]) }}" class="min-w-[170px] flex-1 rounded-xl border p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md {{ $selectedStatus === $filter ? 'border-gray-900 bg-gray-900 text-white' : 'border-gray-200 bg-white text-gray-900' }}">
                    <p class="text-sm {{ $selectedStatus === $filter ? 'text-gray-300' : 'text-gray-500' }}">{{ $label }}</p>
                    <p class="mt-2 text-3xl font-semibold">{{ $stats[$key] }}</p>
                </a>
            @endforeach
            @if ($showCommentTools)
                <a href="{{ route('dashboard', ['filter' => 'comments']) }}" class="min-w-[170px] flex-1 rounded-xl border p-5 text-gray-900 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md {{ $selectedFilter === 'comments' ? 'border-gray-900 bg-gray-900 text-white' : 'border-gray-200 bg-white' }}">
                    <p class="text-sm {{ $selectedFilter === 'comments' ? 'text-gray-300' : 'text-gray-500' }}">Komentar owner</p>
                    <p class="mt-2 text-3xl font-semibold">{{ $stats['comments'] }}</p>
                </a>
            @endif
        </div>

        @if (auth()->user()->role === 'user')
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h3 class="font-semibold text-gray-900">Buat Ticket Baru</h3>
                    <p class="mt-1 text-sm text-gray-500">Isi formulir untuk mengirim pengajuan baru.</p>
                </div>
                <button
                    type="button"
                    class="shrink-0 rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                    @click="showNewTicketForm = true; $nextTick(() => $refs.newTicketRequester.focus())"
                    :aria-expanded="showNewTicketForm.toString()"
                    aria-controls="new-ticket-dialog"
                >Buat Ticket Baru</button>
            </div>

            <div
                id="new-ticket-dialog"
                x-show="showNewTicketForm"
                x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6 sm:px-6"
                role="dialog"
                aria-modal="true"
                aria-labelledby="new-ticket-title"
                @keydown.escape.window="showNewTicketForm = false"
            >
                <div class="absolute inset-0 bg-gray-900/50" @click="showNewTicketForm = false"></div>
                <section class="relative max-h-full w-full max-w-2xl overflow-y-auto rounded-lg bg-white p-6 shadow-xl" @click.stop>
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 id="new-ticket-title" class="text-lg font-semibold text-gray-900">Buat Ticket Baru</h2>
                            <p class="mt-1 text-sm text-gray-500">Isi detail pengajuan yang ingin dikirim.</p>
                        </div>
                        <button
                            type="button"
                            class="rounded-md p-1 text-2xl leading-none text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            aria-label="Tutup formulir tiket baru"
                            @click="showNewTicketForm = false"
                        >&times;</button>
                    </div>
                    <form action="{{ route('tickets.store') }}" method="POST" class="mt-5 space-y-4">
                        @csrf
                        @if ($errors->any())
                            <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
                        @endif
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="new-ticket-requester" class="mb-1 block text-sm font-medium text-gray-700">Nama pengaju</label>
                                <input x-ref="newTicketRequester" id="new-ticket-requester" name="requester_name" value="{{ old('requester_name', auth()->user()->name) }}" required maxlength="120" class="w-full rounded-md border-gray-300 text-sm focus:border-gray-700 focus:ring-gray-700">
                            </div>
                            <div>
                                <label for="new-ticket-whatsapp" class="mb-1 block text-sm font-medium text-gray-700">No. WhatsApp</label>
                                <input id="new-ticket-whatsapp" name="whatsapp_number" value="{{ old('whatsapp_number') }}" placeholder="08xxxxxxxxxx" required class="w-full rounded-md border-gray-300 text-sm focus:border-gray-700 focus:ring-gray-700">
                            </div>
                            <div>
                                <label for="new-ticket-priority" class="mb-1 block text-sm font-medium text-gray-700">Urgensi</label>
                                <select id="new-ticket-priority" name="priority" required class="w-full rounded-md border-gray-300 text-sm focus:border-gray-700 focus:ring-gray-700">
                                    <option value="">Pilih urgensi</option>
                                    <option value="relaxed" @selected(old('priority') === 'relaxed')>Santai</option>
                                    <option value="urgent" @selected(old('priority') === 'urgent')>Mendesak</option>
                                    <option value="critical" @selected(old('priority') === 'critical')>Urgent</option>
                                </select>
                            </div>
                            <div>
                                <label for="new-ticket-target" class="mb-1 block text-sm font-medium text-gray-700">Target sistem</label>
                                <select id="new-ticket-target" name="target" required class="w-full rounded-md border-gray-300 text-sm focus:border-gray-700 focus:ring-gray-700">
                                    <option value="">Pilih sistem</option>
                                    <option value="lppm" @selected(old('target') === 'lppm')>Web LPPM</option>
                                    <option value="lpm" @selected(old('target') === 'lpm')>Web LPM</option>
                                    <option value="ma" @selected(old('target') === 'ma')>Web MA</option>
                                    <option value="trpl" @selected(old('target') === 'trpl')>Web TRPL</option>
                                    <option value="bk" @selected(old('target') === 'bk')>Web BK</option>
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <label for="new-ticket-description" class="mb-1 block text-sm font-medium text-gray-700">Isi ajuan</label>
                                <textarea id="new-ticket-description" name="description" rows="4" placeholder="Jelaskan masalah atau kebutuhan Anda..." required class="w-full rounded-md border-gray-300 text-sm focus:border-gray-700 focus:ring-gray-700">{{ old('description') }}</textarea>
                            </div>
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="showNewTicketForm = false">Batal</button>
                            <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">Kirim pengajuan</button>
                        </div>
                    </form>
                </section>
            </div>
        @endif

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between gap-4 border-b border-gray-200 px-5 py-4">
                <h3 class="font-semibold text-gray-900">{{ $selectedFilter === 'comments' ? 'Pengajuan yang dikomentari owner' : ($selectedStatus === 'all' ? (auth()->user()->role === 'user' ? 'Pengajuan saya' : 'Semua tiket') : match ($selectedStatus) { 'pending' => 'Pengajuan menunggu', 'in_progress' => 'Pengajuan sedang dikerjakan', 'completed' => 'Pengajuan selesai', default => 'Pengajuan' } ) }}</h3>
                @if ($selectedStatus !== 'all' || $selectedFilter === 'comments')
                    <a href="{{ route('dashboard') }}" class="text-sm text-gray-500 underline hover:text-gray-900">Tampilkan semua</a>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1160px] text-left text-sm">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                        <tr>
                            @if ($visibleColumns['ticket_number'])<th class="px-5 py-3">Nomor tiket</th>@endif
                            @if ($visibleColumns['created_at'])<th class="px-5 py-3">Tanggal ajuan</th>@endif
                            @if ($visibleColumns['requester_name'])<th class="px-5 py-3">Nama pengaju</th>@endif
                            @if ($visibleColumns['whatsapp_number'])<th class="px-5 py-3">No. WhatsApp</th>@endif
                            @if ($visibleColumns['description'])<th class="px-5 py-3">Ajuan</th>@endif
                            @if ($visibleColumns['target'])<th class="px-5 py-3">Target</th>@endif
                            @if ($visibleColumns['status'])<th class="px-5 py-3">Status</th>@endif
                            @if ($visibleColumns['comment_tools'])<th class="px-5 py-3">Komentar</th>@endif
                            @if ($visibleColumns['completed_at'])<th class="px-5 py-3">Tanggal selesai</th>@endif
                            @if ($visibleColumns['edit_submission'])<th class="px-5 py-3">EDIT</th>@endif
                            @if ($visibleColumns['actions'])<th class="px-5 py-3">Aksi</th>@endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($tickets as $ticket)
                            @php($priorityClass = match ($ticket->priority->value) {
                                'relaxed' => 'priority-relaxed',
                                'urgent' => 'priority-urgent',
                                'critical' => 'priority-critical',
                                default => 'priority-default',
                            })
                            <tr class="priority-row {{ $priorityClass }}">
                                @if ($visibleColumns['ticket_number'])<td class="px-5 py-4 font-medium text-gray-900">{{ $ticket->ticket_number }}</td>@endif
                                @if ($visibleColumns['created_at'])<td class="whitespace-nowrap px-5 py-4 text-gray-700">{{ $ticket->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i').' WIB' }}</td>@endif
                                @if ($visibleColumns['requester_name'])<td class="px-5 py-4 font-medium text-gray-900">{{ $ticket->requester_name }}</td>@endif
                                @if ($visibleColumns['whatsapp_number'])<td class="whitespace-nowrap px-5 py-4 text-gray-700">{{ $ticket->whatsapp_number }}</td>@endif
                                @if ($visibleColumns['description'])<td class="max-w-xs px-5 py-4 text-gray-700">
                                    <button
                                        type="button"
                                        class="block max-w-xs truncate text-left hover:text-blue-700 hover:underline focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                        title="Klik untuk melihat ajuan lengkap"
                                        @click="description = @js($ticket->description); showDescription = true"
                                    >
                                        {{ \Illuminate\Support\Str::limit($ticket->description, 80) }}
                                    </button>
                                </td>@endif
                                @if ($visibleColumns['target'])<td class="px-5 py-4 text-gray-700">{{ $ticket->target->label() }}</td>@endif
                                @if ($visibleColumns['status'])<td class="px-5 py-4"><span class="status-pill status-{{ $ticket->status->value }} text-xs font-medium">{{ $ticket->status->label() }}</span></td>@endif
                                @if ($visibleColumns['comment_tools'])
                                    <td class="px-5 py-4">
                                        <a href="{{ route('tickets.comments', $ticket) }}" class="whitespace-nowrap rounded-md border border-gray-300 px-3 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50">Chat ({{ $ticket->comments_count ?? $ticket->comments()->count() }})</a>
                                    </td>
                                @endif
                                @if ($visibleColumns['completed_at'])<td class="whitespace-nowrap px-5 py-4 text-gray-700">{{ $ticket->completed_at ? $ticket->completed_at->timezone('Asia/Jakarta')->format('d M Y, H:i').' WIB' : '-' }}</td>@endif
                                @if ($visibleColumns['edit_submission'])
                                    <td class="px-5 py-4">
                                        <a href="{{ route('tickets.edit', $ticket) }}" class="rounded-md bg-gray-900 px-3 py-1 text-xs font-medium text-white">EDIT</a>
                                    </td>
                                @endif
                                @if ($visibleColumns['actions'])
                                    <td class="px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        @if (($featureVisibility['status'] ?? false) && auth()->user()->canManageTickets())
                                        <form method="POST" action="{{ route('tickets.update', $ticket) }}" class="flex gap-2">
                                            @csrf @method('PATCH')
                                            <select name="status" class="rounded-md border-gray-300 py-1 text-xs"><option value="pending" @selected($ticket->status->value === 'pending')>Belum disetujui</option><option value="in_progress" @selected($ticket->status->value === 'in_progress')>Sedang dikerjakan</option><option value="completed" @selected($ticket->status->value === 'completed')>Selesai</option><option value="rejected" @selected($ticket->status->value === 'rejected')>Ditolak</option></select>
                                            <button class="rounded-md bg-gray-900 px-3 py-1 text-xs font-medium text-white">Simpan</button>
                                        </form>
                                        @endif
                                        @if ((auth()->user()->isSupervisor() || auth()->user()->isSuperAdmin()))
                                            <form method="POST" action="{{ route('tickets.destroy', $ticket) }}">
                                                @csrf @method('DELETE')
                                                <button class="rounded-md px-2 py-1 text-xs text-red-600 hover:bg-red-50" onclick="return confirm('Hapus tiket ini?')">Hapus</button>
                                            </form>
                                        @endif
                                    </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="{{ max($columnCount, 1) }}" class="px-5 py-12 text-center text-gray-500">Belum ada pengajuan masuk.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-200 px-5 py-3">{{ $tickets->withQueryString()->links() }}</div>
        </div>

        <div
            x-show="showDescription"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6 sm:px-6"
            role="dialog"
            aria-modal="true"
            aria-labelledby="description-modal-title"
            @keydown.escape.window="showDescription = false"
        >
            <div class="absolute inset-0 bg-gray-900/50" @click="showDescription = false"></div>
            <div class="relative w-full max-w-2xl rounded-lg bg-white p-6 shadow-xl" @click.stop>
                <div class="flex items-start justify-between gap-4">
                    <h2 id="description-modal-title" class="text-lg font-semibold text-gray-900">Ajuan lengkap</h2>
                    <button
                        type="button"
                        class="rounded-md p-1 text-2xl leading-none text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        aria-label="Tutup ajuan lengkap"
                        @click="showDescription = false"
                    >&times;</button>
                </div>
                <p class="mt-4 whitespace-pre-wrap break-words text-sm leading-6 text-gray-700" x-text="description"></p>
            </div>
        </div>
    </div>
</x-app-layout>
