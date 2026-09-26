<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-600">Administrasi sistem</p>
            <h2 class="text-2xl font-semibold text-gray-900">Kelola akun</h2>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
        @endif

        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-5 py-4">
                <h3 class="font-semibold text-gray-900">Semua akun</h3>
                <p class="mt-1 text-sm text-gray-500">Password lama tidak dapat ditampilkan. Isi password baru hanya jika ingin menggantinya.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[980px] text-left text-sm">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                        <tr>
                            <th scope="col" class="px-5 py-3">Nama</th>
                            <th scope="col" class="px-5 py-3">Email</th>
                            <th scope="col" class="px-5 py-3">Role</th>
                            <th scope="col" class="px-5 py-3">Password baru</th>
                            <th scope="col" class="px-5 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($users as $account)
                            <tr>
                                <td class="px-5 py-4">
                                    <form id="account-form-{{ $account->id }}" method="POST" action="{{ route('settings.accounts.update', $account) }}">
                                        @csrf
                                        @method('PUT')
                                    </form>
                                    <input form="account-form-{{ $account->id }}" name="name" value="{{ $account->name }}" required maxlength="255" aria-label="Nama {{ $account->email }}" class="w-48 rounded-md border-gray-300 text-sm focus:border-gray-700 focus:ring-gray-700">
                                </td>
                                <td class="px-5 py-4">
                                    <input form="account-form-{{ $account->id }}" type="email" name="email" value="{{ $account->email }}" required maxlength="255" aria-label="Email {{ $account->email }}" class="w-56 rounded-md border-gray-300 text-sm focus:border-gray-700 focus:ring-gray-700">
                                </td>
                                <td class="px-5 py-4">
                                    <select form="account-form-{{ $account->id }}" name="role" required aria-label="Role {{ $account->email }}" class="w-40 rounded-md border-gray-300 text-sm focus:border-gray-700 focus:ring-gray-700">
                                        @foreach ($roleLabels as $role => $label)
                                            <option value="{{ $role }}" @selected($account->role === $role)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-5 py-4">
                                    <input form="account-form-{{ $account->id }}" type="password" name="password" autocomplete="new-password" placeholder="Kosongkan jika tetap" aria-label="Password baru {{ $account->email }}" class="w-52 rounded-md border-gray-300 text-sm focus:border-gray-700 focus:ring-gray-700">
                                    <input form="account-form-{{ $account->id }}" type="password" name="password_confirmation" autocomplete="new-password" placeholder="Konfirmasi" aria-label="Konfirmasi password baru {{ $account->email }}" class="mt-2 w-52 rounded-md border-gray-300 text-sm focus:border-gray-700 focus:ring-gray-700">
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        <button form="account-form-{{ $account->id }}" type="submit" class="rounded-md bg-gray-900 px-3 py-2 text-xs font-medium text-white hover:bg-gray-700">Simpan</button>
                                        @if (! auth()->user()->is($account))
                                            <form method="POST" action="{{ route('settings.accounts.destroy', $account) }}" onsubmit="return confirm('Hapus akun {{ $account->email }} beserta semua tiket dan komentar terkait secara permanen?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-md border border-red-200 px-3 py-2 text-xs font-medium text-red-700 hover:bg-red-50">Hapus</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-12 text-center text-gray-500">Belum ada akun terdaftar.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <div class="border-t border-gray-200 px-5 py-3">{{ $users->links() }}</div>
            @endif
        </section>
    </div>
</x-app-layout>