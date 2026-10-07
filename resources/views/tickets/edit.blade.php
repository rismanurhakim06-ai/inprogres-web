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

            <form method="POST" action="{{ route('tickets.update-own', $ticket) }}" enctype="multipart/form-data" class="space-y-5">
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

                <div class="space-y-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Lampiran</h3>
                        <p class="mt-1 text-xs text-slate-500">Centang file lama yang ingin diganti, lalu unggah file penggantinya. Maksimal 5 file, 10 MB per file.</p>
                    </div>

                    @if ($ticket->attachments->isNotEmpty())
                        <ul class="space-y-2">
                            @foreach ($ticket->attachments as $attachment)
                                <li class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white p-3">
                                    <a
                                        href="{{ route('tickets.attachments.show', [$ticket, $attachment]) }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="flex min-w-0 items-center gap-3 text-sm font-medium text-slate-700 hover:text-blue-700"
                                    >
                                        @if (str_starts_with($attachment->mime_type, 'image/'))
                                            <img src="{{ route('tickets.attachments.show', [$ticket, $attachment]) }}" alt="" class="h-10 w-10 shrink-0 rounded-lg object-cover">
                                        @else
                                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-blue-50 text-blue-600">
                                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V10Z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 3v7h7m-11 4h6m-6 3h6" />
                                                </svg>
                                            </span>
                                        @endif
                                        <span class="min-w-0">
                                            <span class="block truncate">{{ $attachment->original_name }}</span>
                                            <span class="block text-xs font-normal text-slate-500">{{ \Illuminate\Support\Number::fileSize($attachment->size) }}</span>
                                        </span>
                                    </a>
                                    <label class="inline-flex shrink-0 items-center gap-2 text-sm text-red-700">
                                        <input type="checkbox" name="remove_attachments[]" value="{{ $attachment->id }}" @checked(in_array((string) $attachment->id, old('remove_attachments', []), true)) class="rounded border-slate-300 text-red-600 focus:ring-red-500">
                                        Ganti / hapus
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="rounded-lg border border-dashed border-slate-300 bg-white px-3 py-4 text-center text-sm text-slate-500">Belum ada lampiran pada tiket ini.</p>
                    @endif

                    <div>
                        <x-input-label for="attachments" value="Unggah file pengganti atau tambahan" />
                        <input id="attachments" name="attachments[]" type="file" multiple accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,image/jpeg,image/png,image/webp,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="mt-1 block w-full rounded-xl border border-slate-200 bg-white text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100 focus:border-blue-500 focus:ring-blue-500">
                        <x-input-error :messages="$errors->get('attachments')" class="mt-2" />
                        @foreach ($errors->get('attachments.*') as $attachmentErrors)
                            @foreach ($attachmentErrors as $attachmentError)
                                <p class="mt-2 text-sm text-red-600">{{ $attachmentError }}</p>
                            @endforeach
                        @endforeach
                        <x-input-error :messages="$errors->get('remove_attachments')" class="mt-2" />
                        @foreach ($errors->get('remove_attachments.*') as $removalErrors)
                            @foreach ($removalErrors as $removalError)
                                <p class="mt-2 text-sm text-red-600">{{ $removalError }}</p>
                            @endforeach
                        @endforeach
                    </div>
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
                        <x-text-input id="target" name="target" list="target-options" class="mt-1 block w-full" :value="old('target', $ticket->targetLabel())" required maxlength="255" placeholder="Pilih atau ketik nama sistem" />
                        <datalist id="target-options">
                            @foreach (\App\Enums\TicketTarget::cases() as $target)
                                <option value="{{ $target->label() }}"></option>
                            @endforeach
                        </datalist>
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
