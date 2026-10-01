@if (session('success') || session('created_ticket'))
    <div
        x-data="{ visible: true }"
        x-init="window.setTimeout(() => visible = false, 6000)"
        x-show="visible"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="scale-95 opacity-0"
        x-transition:enter-end="scale-100 opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="scale-95 opacity-0"
        class="pointer-events-none fixed inset-0 z-[100] flex items-center justify-center px-4"
        role="status"
        aria-live="polite"
    >
        <div class="pointer-events-auto flex w-full max-w-md items-center gap-3 rounded-lg border border-emerald-200 bg-white p-4 text-sm text-emerald-900 shadow-xl">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-lg font-bold text-emerald-700" aria-hidden="true">&#10003;</span>
            <div class="min-w-0 flex-1 space-y-1">
                @if (session('success'))
                    <p>{{ session('success') }}</p>
                @endif
                @if (session('created_ticket'))
                    <p>Nomor tiket baru: <strong>{{ session('created_ticket') }}</strong></p>
                @endif
            </div>
            <button type="button" class="shrink-0 rounded-md px-2 text-lg leading-5 text-gray-500 hover:bg-gray-100 hover:text-gray-900" aria-label="Tutup pemberitahuan" @click="visible = false">&times;</button>
        </div>
    </div>
@endif