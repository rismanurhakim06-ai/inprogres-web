<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8 lg:py-8" x-data="{ showDescription: false, description: '', showNewTicketForm: @js($errors->any()), showTicketSearch: false }">
        @if (($featureVisibility['summary_total'] ?? false) || ($featureVisibility['summary_pending'] ?? false) || ($featureVisibility['summary_in_progress'] ?? false) || ($featureVisibility['summary_completed'] ?? false) || ($featureVisibility['summary_comments'] ?? false))
            <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,220px),1fr))] gap-4">
                @foreach ([['summary_total', 'total', 'Total tiket', 'all'], ['summary_pending', 'pending', 'Menunggu', 'pending'], ['summary_in_progress', 'in_progress', 'Dikerjakan', 'in_progress'], ['summary_completed', 'completed', 'Selesai', 'completed']] as [$feature, $key, $label, $filter])
                    @if ($featureVisibility[$feature] ?? false)
                        @php($summaryColor = match ($key) {
                            'pending' => 'bg-amber-50 text-amber-600',
                            'in_progress' => 'bg-blue-50 text-blue-600',
                            'completed' => 'bg-emerald-50 text-emerald-600',
                            default => 'bg-slate-100 text-slate-500',
                        })
                        @php($summaryDotColor = match ($key) {
                            'pending' => 'bg-amber-500',
                            'in_progress' => 'bg-blue-500',
                            'completed' => 'bg-emerald-500',
                            default => 'bg-slate-400',
                        })
                        @php($summaryDescription = match ($key) {
                            'total' => 'Seluruh ajuan masuk',
                            'pending' => 'Perlu respons / approval',
                            'in_progress' => 'Sedang ditangani tim',
                            default => 'Kasus terselesaikan',
                        })
                        <a href="{{ route('dashboard', $filter === 'all' ? [] : ['status' => $filter]) }}" class="group flex min-h-36 min-w-0 flex-col justify-between rounded-2xl border p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md {{ $selectedStatus === $filter ? 'border-slate-900 bg-slate-950 text-white shadow-slate-900/10' : 'border-slate-200 bg-white text-slate-900' }}">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-medium {{ $selectedStatus === $filter ? 'text-slate-300' : 'text-slate-600' }}">{{ $label }}</p>
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl {{ $selectedStatus === $filter ? 'bg-white/10 text-slate-200' : $summaryColor }}">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        @if ($key === 'total')
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 7.5A2.5 2.5 0 0 1 7.5 5h9A2.5 2.5 0 0 1 19 7.5v9a2.5 2.5 0 0 1-2.5 2.5h-9A2.5 2.5 0 0 1 5 16.5v-9Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 9h6m-6 3h6m-6 3h3" />
                                        @elseif ($key === 'pending')
                                            <circle cx="12" cy="12" r="8" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l2.5 1.5" />
                                        @elseif ($key === 'in_progress')
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m13 2-8 12h6l-1 8 9-13h-6l1-7Z" />
                                        @else
                                            <circle cx="12" cy="12" r="8" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.5 12 2.25 2.25L15.5 9.5" />
                                        @endif
                                    </svg>
                                </span>
                            </div>
                            <div>
                                <p class="text-3xl font-bold tracking-tight">{{ $stats[$key] }}</p>
                                <p class="mt-2 flex items-center gap-1.5 text-xs {{ $selectedStatus === $filter ? 'text-slate-300' : 'text-slate-500' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $selectedStatus === $filter ? 'bg-white/70' : $summaryDotColor }}"></span>
                                    {{ $summaryDescription }}
                                </p>
                            </div>
                        </a>
                    @endif
                @endforeach
                @if ($featureVisibility['summary_comments'] ?? false)
                    @if ($showCommentTools)
                        <a href="{{ route('dashboard', ['filter' => 'comments']) }}" class="group flex min-h-36 min-w-0 flex-col justify-between rounded-2xl border p-5 text-slate-900 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md {{ $selectedFilter === 'comments' ? 'border-slate-900 bg-slate-950 text-white shadow-slate-900/10' : 'border-slate-200 bg-white' }}">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-medium {{ $selectedFilter === 'comments' ? 'text-slate-300' : 'text-slate-600' }}">Komentar staf</p>
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl {{ $selectedFilter === 'comments' ? 'bg-white/10 text-slate-200' : 'bg-violet-50 text-violet-600' }}">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 18.5 3.5 20l1-4A8.5 8.5 0 1 1 7 18.5Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8m-8 4h5" />
                                    </svg>
                                </span>
                            </div>
                            <div>
                                <p class="text-3xl font-bold tracking-tight">{{ $stats['comments'] }}</p>
                                <p class="mt-2 flex items-center gap-1.5 text-xs {{ $selectedFilter === 'comments' ? 'text-slate-300' : 'text-slate-500' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $selectedFilter === 'comments' ? 'bg-white/70' : 'bg-violet-500' }}"></span>
                                    Interaksi aktif
                                </p>
                            </div>
                        </a>
                    @else
                        <div class="flex min-h-36 min-w-0 flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 text-slate-900 shadow-sm">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-medium text-slate-600">Komentar staf</p>
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-violet-50 text-violet-600">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 18.5 3.5 20l1-4A8.5 8.5 0 1 1 7 18.5Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8m-8 4h5" />
                                    </svg>
                                </span>
                            </div>
                            <div>
                                <p class="text-3xl font-bold tracking-tight">{{ $stats['comments'] }}</p>
                                <p class="mt-2 flex items-center gap-1.5 text-xs text-slate-500"><span class="h-1.5 w-1.5 rounded-full bg-violet-500"></span>Interaksi aktif</p>
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        @endif

        @if (auth()->user()->role === 'user')
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4">
                    <h3 class="font-semibold text-slate-900">Buat Ticket Baru</h3>
                    <p class="mt-1 text-sm text-slate-500">Isi formulir untuk mengirim pengajuan baru.</p>
                </div>
                <div class="flex flex-wrap gap-2 sm:shrink-0">
                    <button
                        type="button"
                        class="shrink-0 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2"
                        @click="showTicketSearch = true; $nextTick(() => $refs.ticketSearchInput.focus())"
                        :aria-expanded="showTicketSearch.toString()"
                        aria-controls="ticket-search-dialog"
                    >Cari Tiket</button>
                    <button
                        type="button"
                        class="shrink-0 rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2"
                        @click="showNewTicketForm = true; $nextTick(() => $refs.newTicketRequester.focus())"
                        :aria-expanded="showNewTicketForm.toString()"
                        aria-controls="new-ticket-dialog"
                    >Buat Ticket Baru</button>
                </div>
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
                    <form action="{{ route('tickets.store') }}" method="POST" enctype="multipart/form-data" class="mt-5 space-y-4">
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
                                <input id="new-ticket-target" name="target" list="new-ticket-target-options" value="{{ old('target') }}" placeholder="Pilih atau ketik nama sistem" required maxlength="255" class="w-full rounded-md border-gray-300 text-sm focus:border-gray-700 focus:ring-gray-700">
                                <datalist id="new-ticket-target-options">
                                    @foreach (\App\Enums\TicketTarget::cases() as $target)
                                        <option value="{{ $target->label() }}"></option>
                                    @endforeach
                                </datalist>
                            </div>
                            <div class="sm:col-span-2">
                                <label for="new-ticket-description" class="mb-1 block text-sm font-medium text-gray-700">Isi ajuan</label>
                                <textarea id="new-ticket-description" name="description" rows="4" placeholder="Jelaskan masalah atau kebutuhan Anda..." required class="w-full rounded-md border-gray-300 text-sm focus:border-gray-700 focus:ring-gray-700">{{ old('description') }}</textarea>
                            </div>
                            <div class="sm:col-span-2">
                                <label for="new-ticket-attachments" class="mb-1 block text-sm font-medium text-gray-700">Lampiran (opsional)</label>
                                <input id="new-ticket-attachments" name="attachments[]" type="file" multiple accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,image/jpeg,image/png,image/webp,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="w-full rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100 focus:border-blue-500 focus:ring-blue-500">
                                <p class="mt-2 text-xs text-slate-500">Maksimal 5 file, 10 MB per file. Format: JPG, PNG, WEBP, PDF, DOC, DOCX, XLS, XLSX.</p>
                                @error('attachments')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                @foreach ($errors->get('attachments.*') as $attachmentErrors)
                                    @foreach ($attachmentErrors as $attachmentError)
                                        <p class="mt-1 text-sm text-red-600">{{ $attachmentError }}</p>
                                    @endforeach
                                @endforeach
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

        <div
            id="ticket-search-dialog"
            x-show="showTicketSearch"
            x-cloak
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6 sm:px-6"
            role="dialog"
            aria-modal="true"
            aria-labelledby="ticket-search-title"
            @keydown.escape.window="showTicketSearch = false"
        >
            <div class="absolute inset-0 bg-gray-900/50" @click="showTicketSearch = false"></div>
            <section class="relative w-full max-w-2xl rounded-lg bg-white p-6 shadow-xl" @click.stop>
                <div class="mb-5 flex items-start justify-between gap-4">
                    <div>
                        <h2 id="ticket-search-title" class="text-lg font-semibold text-gray-900">Cari Tiket</h2>
                        <p class="mt-1 text-sm text-gray-500">Cari berdasarkan nomor, nama, WhatsApp, atau isi ajuan.</p>
                    </div>
                    <button type="button" class="rounded-md p-1 text-2xl leading-none text-gray-500 hover:bg-gray-100 hover:text-gray-900" aria-label="Tutup pencarian tiket" @click="showTicketSearch = false">&times;</button>
                </div>
                <form action="{{ route('dashboard') }}" method="GET" class="space-y-4">
                    @if ($selectedStatus !== 'all')<input type="hidden" name="status" value="{{ $selectedStatus }}">@endif
                    @if ($selectedFilter !== 'all')<input type="hidden" name="filter" value="{{ $selectedFilter }}">@endif
                    <div>
                        <label for="ticket-search" class="mb-1 block text-xs font-medium text-gray-600">Kata pencarian</label>
                        <input x-ref="ticketSearchInput" id="ticket-search" type="search" name="search" value="{{ $search }}" placeholder="Contoh: INP-260919-AB12C atau nama pengaju" class="w-full rounded-md border-gray-300 text-sm focus:border-gray-700 focus:ring-gray-700">
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label for="ticket-sort" class="mb-1 block text-xs font-medium text-gray-600">Urutkan</label>
                            <select id="ticket-sort" name="sort" class="w-full rounded-md border-gray-300 text-sm focus:border-gray-700 focus:ring-gray-700">
                                <option value="created_at" @selected($sort === 'created_at')>Tanggal ajuan</option>
                                <option value="ticket_number" @selected($sort === 'ticket_number')>Nomor tiket</option>
                                <option value="requester_name" @selected($sort === 'requester_name')>Nama pengaju</option>
                                <option value="whatsapp_number" @selected($sort === 'whatsapp_number')>No. WhatsApp</option>
                                <option value="target" @selected($sort === 'target')>Target</option>
                                <option value="status" @selected($sort === 'status')>Status</option>
                                <option value="completed_at" @selected($sort === 'completed_at')>Tanggal selesai</option>
                            </select>
                        </div>
                        <div>
                            <label for="ticket-direction" class="mb-1 block text-xs font-medium text-gray-600">Arah</label>
                            <select id="ticket-direction" name="direction" class="w-full rounded-md border-gray-300 text-sm focus:border-gray-700 focus:ring-gray-700">
                                <option value="desc" @selected($direction === 'desc')>Urutan turun</option>
                                <option value="asc" @selected($direction === 'asc')>Urutan naik</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="showTicketSearch = false">Batal</button>
                        <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">Cari Tiket</button>
                    </div>
                </form>
            </section>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div class="flex items-center gap-3">
                    <h3 class="font-semibold text-slate-900">{{ $selectedFilter === 'comments' ? 'chat' : ($selectedStatus === 'all' ? (auth()->user()->role === 'user' ? 'Pengajuan saya' : 'Semua tiket') : match ($selectedStatus) { 'pending' => 'Pengajuan menunggu', 'in_progress' => 'Pengajuan sedang dikerjakan', 'completed' => 'Pengajuan selesai', default => 'Pengajuan' } ) }}</h3>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ number_format($tickets->total(), 0, ',', '.') }} tiket</span>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if (auth()->user()->role !== 'user')
                        <button type="button" class="inline-flex items-center gap-2 whitespace-nowrap rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm font-medium text-slate-600 transition hover:bg-white hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-400" @click="showTicketSearch = true; $nextTick(() => $refs.ticketSearchInput.focus())" :aria-expanded="showTicketSearch.toString()" aria-controls="ticket-search-dialog">
                            <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <circle cx="10.8" cy="10.8" r="6.3" />
                                <path stroke-linecap="round" d="m15.5 15.5 4 4" />
                            </svg>
                            Cari Tiket
                        </button>
                    @endif
                    @if ($selectedStatus !== 'all' || $selectedFilter === 'comments')
                        <a href="{{ route('dashboard') }}" class="whitespace-nowrap rounded-xl border border-slate-200 px-3.5 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">Tampilkan semua</a>
                    @endif
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1160px] text-left text-sm">
                    <thead class="bg-slate-50 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                        <tr>
                            @if ($visibleColumns['ticket_number'])<th class="px-5 py-3.5">Nomor tiket</th>@endif
                            @if ($visibleColumns['created_at'])<th class="px-5 py-3.5">Tanggal ajuan</th>@endif
                            @if ($visibleColumns['requester_name'])<th class="px-5 py-3.5">Nama pengaju</th>@endif
                            @if ($visibleColumns['whatsapp_number'])<th class="px-5 py-3.5">No. WhatsApp</th>@endif
                            @if ($visibleColumns['description'])<th class="px-5 py-3.5">Ajuan</th>@endif
                            @if ($visibleColumns['target'])<th class="px-5 py-3.5">Target</th>@endif
                            @if ($visibleColumns['status'])<th class="px-5 py-3.5">Status</th>@endif
                            @if ($visibleColumns['new_comment'])<th class="px-5 py-3.5">New Comment</th>@endif
                            @if ($visibleColumns['comment_tools'])<th class="px-5 py-3.5">Komentar</th>@endif
                            @if ($visibleColumns['completed_at'])<th class="px-5 py-3.5">Tanggal selesai</th>@endif
                            @if ($visibleColumns['edit_submission'])<th class="px-5 py-3.5">EDIT</th>@endif
                            @if ($visibleColumns['actions'])<th class="px-5 py-3.5">Aksi</th>@endif
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
                            @php($priorityBadgeClass = match ($ticket->priority->value) {
                                'relaxed' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                                'urgent' => 'border-amber-200 bg-amber-50 text-amber-700',
                                'critical' => 'border-red-200 bg-red-50 text-red-700',
                                default => 'border-slate-200 bg-slate-50 text-slate-600',
                            })
                            <tr class="priority-row {{ $priorityClass }} transition-colors">
                                @if ($visibleColumns['ticket_number'])
                                    <td class="px-5 py-4 font-medium text-slate-900">
                                        <div class="flex min-w-28 flex-col items-start gap-1.5">
                                            <span class="rounded-md border border-blue-100 bg-blue-50 px-2 py-1 font-mono text-[11px] font-semibold text-blue-700">{{ $ticket->ticket_number }}</span>
                                            <span class="inline-flex rounded-full border px-2 py-0.5 text-[10px] font-semibold {{ $priorityBadgeClass }}">{{ $ticket->priority->label() }}</span>
                                        </div>
                                    </td>
                                @endif
                                @if ($visibleColumns['created_at'])<td class="whitespace-nowrap px-5 py-4 text-xs text-slate-600">{{ $ticket->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i').' WIB' }}</td>@endif
                                @if ($visibleColumns['requester_name'])<td class="px-5 py-4 font-medium text-slate-800">{{ $ticket->requester_name }}</td>@endif
                                @if ($visibleColumns['whatsapp_number'])<td class="whitespace-nowrap px-5 py-4 text-xs text-slate-600">{{ $ticket->whatsapp_number }}</td>@endif
                                @if ($visibleColumns['description'])
                                    <td class="max-w-xs px-5 py-4 text-slate-600">
                                        <div class="space-y-2">
                                            <button
                                                type="button"
                                                class="block max-w-xs truncate text-left text-xs leading-5 hover:text-blue-700 hover:underline focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                                title="Klik untuk melihat ajuan lengkap"
                                                @click="description = @js($ticket->description); showDescription = true"
                                            >
                                                {{ \Illuminate\Support\Str::limit($ticket->description, 80) }}
                                            </button>
                                            @if ($ticket->attachments->isNotEmpty())
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach ($ticket->attachments as $attachment)
                                                        <a
                                                            href="{{ route('tickets.attachments.show', [$ticket, $attachment]) }}"
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            title="{{ $attachment->original_name }}"
                                                            class="inline-flex max-w-56 items-center gap-2 rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-[11px] font-medium text-slate-600 transition hover:border-blue-200 hover:text-blue-700"
                                                        >
                                                            @if (str_starts_with($attachment->mime_type, 'image/'))
                                                                <img src="{{ route('tickets.attachments.show', [$ticket, $attachment]) }}" alt="" loading="lazy" class="h-8 w-8 shrink-0 rounded object-cover">
                                                            @else
                                                                <svg class="h-4 w-4 shrink-0 text-blue-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V10Z" />
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 3v7h7m-11 4h6m-6 3h6" />
                                                                </svg>
                                                            @endif
                                                            <span class="truncate">{{ \Illuminate\Support\Str::limit($attachment->original_name, 28) }}</span>
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                @endif
                                @if ($visibleColumns['target'])<td class="px-5 py-4 text-slate-600">{{ $ticket->targetLabel() }}</td>@endif
                                @if ($visibleColumns['status'])<td class="px-5 py-4"><span class="status-pill status-{{ $ticket->status->value }} text-xs font-medium">{{ $ticket->status->label() }}</span></td>@endif
                                @if ($visibleColumns['new_comment'])
                                    <td class="px-5 py-4">
                                        @if ((auth()->user()->role === 'user' && $ticket->unread_by_user) || (auth()->user()->role === 'supervisor' && $ticket->unread_by_supervisor))
                                            <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">New Comment</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                @endif
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
                            <tr><td colspan="{{ max($columnCount, 1) }}" class="px-5 py-12 text-center text-gray-500">{{ $search !== '' ? 'Tidak ada tiket yang cocok dengan pencarian.' : 'Belum ada pengajuan masuk.' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 bg-white px-5 py-3">{{ $tickets->withQueryString()->links() }}</div>
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
