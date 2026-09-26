<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-600">Administrasi sistem</p>
            <h2 class="text-2xl font-semibold text-gray-900">Persetujuan akun</h2>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-5 py-4">
                <h3 class="font-semibold text-gray-900">Pendaftaran yang menunggu</h3>
                <p class="mt-1 text-sm text-gray-500">Setujui akun agar pemiliknya dapat masuk dan membuat tiket.</p>
            </div>

            <div class="divide-y divide-gray-100">
                @forelse ($pendingUsers as $pendingUser)
                    <div class="flex flex-col gap-4 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-gray-900">{{ $pendingUser->name }}</p>
                            <p class="truncate text-sm text-gray-500">{{ $pendingUser->email }}</p>
                            <p class="mt-1 text-xs text-gray-400">Mendaftar {{ $pendingUser->created_at->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') }} WIB</p>
                        </div>
                        <form method="POST" action="{{ route('account-approvals.store', $pendingUser) }}">
                            @csrf
                            <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">Setujui akun</button>
                        </form>
                    </div>
                @empty
                    <p class="px-5 py-12 text-center text-sm text-gray-500">Tidak ada pendaftaran yang menunggu persetujuan.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>