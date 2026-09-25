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

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8" x-data="{ showDescription: false, description: '' }">
        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        <div class="flex flex-nowrap gap-4 overflow-x-auto pb-1">
            @foreach ([['total', 'Total tiket', 'all'], ['pending', 'Menunggu', 'pending'], ['in_progress', 'Dikerjakan', 'in_progress'], ['completed', 'Selesai', 'completed']] as [$key, $label, $filter])
            <a href="{{ route('dashboard', $filter === 'all' ? [] : ['status' => $filter]) }}" class="min-w-[170px] flex-1 rounded-xl border p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md {{ $selectedStatus === $filter ? 'border-gray-900 bg-gray-900 text-white' : 'border-gray-200 bg-white text-gray-900' }}">
                    <p class="text-sm {{ $selectedStatus === $filter ? 'text-gray-300' : 'text-gray-500' }}">{{ $label }}</p>
                    <p class="mt-2 text-3xl font-semibold">{{ $stats[$key] }}</p>
                </a>
            @endforeach
            @if (auth()->user()->role !== 'supervisor')
                <a href="{{ route('dashboard', ['filter' => 'comments']) }}" class="min-w-[170px] flex-1 rounded-xl border p-5 text-gray-900 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md {{ $selectedFilter === 'comments' ? 'border-gray-900 bg-gray-900 text-white' : 'border-gray-200 bg-white' }}">
                    <p class="text-sm {{ $selectedFilter === 'comments' ? 'text-gray-300' : 'text-gray-500' }}">Komentar owner</p>
                    <p class="mt-2 text-3xl font-semibold">{{ $stats['comments'] }}</p>
                </a>
            @endif
        </div>

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
                        <tr><th class="px-5 py-3">Nomor tiket</th><th class="px-5 py-3">Nama pengaju</th><th class="px-5 py-3">No. WhatsApp</th><th class="px-5 py-3">Ajuan</th><th class="px-5 py-3">Target</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Tanggal selesai</th>@if (auth()->user()->role !== 'supervisor')<th class="px-5 py-3">Komentar</th>@endif<th class="px-5 py-3">{{ auth()->user()->role === 'user' ? 'Edit' : 'Aksi' }}</th></tr>
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
                                <td class="px-5 py-4"><strong class="block text-gray-900">{{ $ticket->ticket_number }}</strong><span class="text-gray-500">{{ $ticket->created_at->timezone('Asia/Jakarta')->format('d M Y') }}</span></td>
                                <td class="px-5 py-4 font-medium text-gray-900">{{ $ticket->requester_name }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-gray-700">{{ $ticket->whatsapp_number }}</td>
                                <td class="max-w-xs px-5 py-4 text-gray-700">
                                    <button
                                        type="button"
                                        class="block max-w-xs truncate text-left hover:text-blue-700 hover:underline focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                        title="Klik untuk melihat ajuan lengkap"
                                        @click="description = @js($ticket->description); showDescription = true"
                                    >
                                        {{ \Illuminate\Support\Str::limit($ticket->description, 80) }}
                                    </button>
                                </td>
                                <td class="px-5 py-4 text-gray-700">{{ $ticket->target->label() }}</td>
                                <td class="px-5 py-4"><span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">{{ $ticket->status->label() }}</span></td>
                                <td class="whitespace-nowrap px-5 py-4 text-gray-700">{{ $ticket->completed_at ? $ticket->completed_at->timezone('Asia/Jakarta')->format('d M Y, H:i').' WIB' : '-' }}</td>
                                @if (auth()->user()->role !== 'supervisor')
                                    <td class="px-5 py-4">
                                        <a href="{{ route('tickets.comments', $ticket) }}" class="whitespace-nowrap rounded-md border border-gray-300 px-3 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50">Chat ({{ $ticket->comments_count ?? $ticket->comments()->count() }})</a>
                                    </td>
                                @endif
                                <td class="px-5 py-4">
                                    @if (auth()->user()->role === 'user')
                                        <a href="{{ route('tickets.edit', $ticket) }}" class="rounded-md bg-gray-900 px-3 py-1 text-xs font-medium text-white">Edit pengajuan</a>
                                    @else
                                    <div class="flex items-center gap-2">
                                        <form method="POST" action="{{ route('tickets.update', $ticket) }}" class="flex gap-2">
                                            @csrf @method('PATCH')
                                            <select name="status" class="rounded-md border-gray-300 py-1 text-xs"><option value="pending" @selected($ticket->status->value === 'pending')>Belum disetujui</option><option value="in_progress" @selected($ticket->status->value === 'in_progress')>Sedang dikerjakan</option><option value="completed" @selected($ticket->status->value === 'completed')>Selesai</option><option value="rejected" @selected($ticket->status->value === 'rejected')>Ditolak</option></select>
                                            <button class="rounded-md bg-gray-900 px-3 py-1 text-xs font-medium text-white">Simpan</button>
                                        </form>
                                        @if (auth()->user()->isSupervisor())
                                            <form method="POST" action="{{ route('tickets.destroy', $ticket) }}">
                                                @csrf @method('DELETE')
                                                <button class="rounded-md px-2 py-1 text-xs text-red-600 hover:bg-red-50" onclick="return confirm('Hapus tiket ini?')">Hapus</button>
                                            </form>
                                        @endif
                                    </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ auth()->user()->role === 'supervisor' ? 8 : 9 }}" class="px-5 py-12 text-center text-gray-500">Belum ada pengajuan masuk.</td></tr>
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
