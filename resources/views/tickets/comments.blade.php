<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-600">Percakapan pengajuan</p>
                <h2 class="font-semibold text-2xl text-gray-900">{{ $ticket->ticket_number }}</h2>
            </div>
            <a href="{{ route('dashboard') }}" class="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">Kembali</a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 pb-4">
                <div>
                    <p class="text-sm text-gray-500">{{ $ticket->requester_name }} · {{ $ticket->target->label() }}</p>
                    <p class="mt-1 text-sm text-gray-700">{{ $ticket->description }}</p>
                </div>
                <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">{{ $ticket->status->label() }}</span>
            </div>

            <div class="mt-5 space-y-4">
                @forelse ($ticket->comments as $comment)
                    @php($isOwnMessage = $comment->user_id === auth()->id())
                    <div class="flex {{ $isOwnMessage ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[85%] rounded-2xl px-4 py-3 {{ $isOwnMessage ? 'rounded-br-sm bg-gray-900 text-white' : 'rounded-bl-sm bg-gray-100 text-gray-900' }}">
                            <div class="flex items-center justify-between gap-4 text-xs {{ $isOwnMessage ? 'text-gray-300' : 'text-gray-500' }}">
                                <span class="font-semibold">{{ $comment->user->name }}</span>
                                <time datetime="{{ $comment->created_at->toIso8601String() }}">{{ $comment->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }}</time>
                            </div>
                            <p class="mt-2 whitespace-pre-wrap break-words text-sm leading-6">{{ $comment->body }}</p>
                        </div>
                    </div>
                @empty
                    <p class="py-8 text-center text-sm text-gray-500">Belum ada komentar. Mulai percakapan untuk ajuan ini.</p>
                @endforelse
            </div>
        </div>

        <form method="POST" action="{{ route('tickets.comments.store', $ticket) }}" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            @csrf
            <x-input-label for="body" value="Tulis pesan" />
            <textarea id="body" name="body" rows="4" class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Tulis komentar atau balasan..." required>{{ old('body') }}</textarea>
            <x-input-error :messages="$errors->get('body')" class="mt-2" />
            <div class="mt-4 flex justify-end">
                <x-primary-button>Kirim pesan</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
