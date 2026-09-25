<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-600">Pengajuan saya</p>
            <h2 class="font-semibold text-2xl text-gray-900">Edit pengajuan</h2>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-6 flex items-center justify-between gap-4 border-b border-gray-100 pb-4">
                <div>
                    <p class="text-sm text-gray-500">Nomor tiket</p>
                    <p class="font-semibold text-gray-900">{{ $ticket->ticket_number }}</p>
                </div>
                <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">{{ $ticket->status->label() }}</span>
            </div>

            <form method="POST" action="{{ route('tickets.update-own', $ticket) }}" class="space-y-5">
                @csrf
                @method('PATCH')

                <div>
                    <x-input-label for="requester_name" value="Nama pengaju" />
                    <x-text-input id="requester_name" name="requester_name" class="mt-1 block w-full" :value="old('requester_name', $ticket->requester_name)" required />
                    <x-input-error :messages="$errors->get('requester_name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="whatsapp_number" value="No. WhatsApp" />
                    <x-text-input id="whatsapp_number" name="whatsapp_number" class="mt-1 block w-full" :value="old('whatsapp_number', $ticket->whatsapp_number)" required />
                    <x-input-error :messages="$errors->get('whatsapp_number')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="description" value="Isi ajuan" />
                    <textarea id="description" name="description" rows="6" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>{{ old('description', $ticket->description) }}</textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="priority" value="Urgensi" />
                        <select id="priority" name="priority" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            @foreach (\App\Enums\TicketPriority::cases() as $priority)
                                <option value="{{ $priority->value }}" @selected(old('priority', $ticket->priority->value) === $priority->value)>{{ $priority->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('priority')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="target" value="Target sistem" />
                        <select id="target" name="target" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            @foreach (\App\Enums\TicketTarget::cases() as $target)
                                <option value="{{ $target->value }}" @selected(old('target', $ticket->target->value) === $target->value)>{{ $target->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('target')" class="mt-2" />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-5">
                    <a href="{{ route('dashboard') }}" class="rounded-md px-4 py-2 text-sm text-gray-600 hover:bg-gray-100">Batal</a>
                    <x-primary-button>Simpan perubahan</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
