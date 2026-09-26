<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-600">{{ auth()->user()->isSuperAdmin() ? 'Administrasi sistem' : 'Manajemen akun' }}</p>
            <h2 class="text-2xl font-semibold text-gray-900">{{ auth()->user()->isSuperAdmin() ? 'Pengaturan tampilan role' : 'Kelola akun' }}</h2>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
        @endif

        @if (auth()->user()->isAdmin())
            <form method="POST" action="{{ route('settings.roles.users.store') }}" class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                @csrf
                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="font-semibold text-gray-900">{{ auth()->user()->role === 'admin' ? 'Buat akun User' : 'Buat akun dengan role' }}</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ auth()->user()->role === 'admin' ? 'Admin hanya dapat membuat akun User.' : 'Tambahkan akun User, Admin, atau Supervisor.' }}</p>
                </div>
                <div class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
                    <div>
                        <label for="staff-name" class="mb-1 block text-sm font-medium text-gray-700">Nama</label>
                        <input id="staff-name" name="name" value="{{ old('name') }}" required maxlength="255" class="w-full rounded-md border-gray-300 text-sm focus:border-gray-700 focus:ring-gray-700">
                    </div>
                    <div>
                        <label for="staff-email" class="mb-1 block text-sm font-medium text-gray-700">Email</label>
                        <input id="staff-email" type="email" name="email" value="{{ old('email') }}" required maxlength="255" class="w-full rounded-md border-gray-300 text-sm focus:border-gray-700 focus:ring-gray-700">
                    </div>
                    <div>
                        <label for="staff-role" class="mb-1 block text-sm font-medium text-gray-700">Role</label>
                        <select id="staff-role" name="role" required class="w-full rounded-md border-gray-300 text-sm focus:border-gray-700 focus:ring-gray-700">
                            @foreach ($creatableRoles as $role => $label)
                                <option value="{{ $role }}" @selected(old('role', 'user') === $role)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="staff-password" class="mb-1 block text-sm font-medium text-gray-700">Password</label>
                        <input id="staff-password" type="password" name="password" required autocomplete="new-password" class="w-full rounded-md border-gray-300 text-sm focus:border-gray-700 focus:ring-gray-700">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="staff-password-confirmation" class="mb-1 block text-sm font-medium text-gray-700">Konfirmasi password</label>
                        <input id="staff-password-confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="w-full rounded-md border-gray-300 text-sm focus:border-gray-700 focus:ring-gray-700 sm:max-w-md">
                    </div>
                </div>
                <div class="flex justify-end border-t border-gray-200 px-5 py-4">
                    <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">Buat akun</button>
                </div>
            </form>
        @endif

        @if (auth()->user()->isSuperAdmin())
        <form method="POST" action="{{ route('settings.roles.update') }}" class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            @csrf
            @method('PUT')

            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
                <div>
                    <h3 class="font-semibold text-gray-900">Pengaturan tampilan dashboard</h3>
                    <p class="mt-1 text-sm text-gray-500">Pilih kartu ringkasan dan kolom tabel yang ditampilkan untuk setiap role.</p>
                </div>
                <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">Simpan pengaturan</button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                        <tr>
                            <th scope="col" class="px-5 py-3">Fitur</th>
                            @foreach ($roleLabels as $role => $label)
                                <th scope="col" class="px-5 py-3 text-center">
                                    <input type="hidden" name="settings[{{ $role }}][_present]" value="1">
                                    {{ $label }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach (['Kartu ringkasan' => $summaryFeatures, 'Kolom tabel tiket' => $tableFeatures] as $groupLabel => $groupFeatures)
                            <tr>
                                <th colspan="{{ count($roleLabels) + 1 }}" class="bg-gray-50 px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">{{ $groupLabel }}</th>
                            </tr>
                            @foreach ($groupFeatures as $feature => $label)
                                <tr>
                                    <th scope="row" class="px-5 py-4 font-medium text-gray-900">{{ $label }}</th>
                                    @foreach ($roleLabels as $role => $roleLabel)
                                        <td class="px-5 py-4 text-center">
                                            <input type="hidden" name="settings[{{ $role }}][{{ $feature }}]" value="0">
                                            <input
                                                type="checkbox"
                                                name="settings[{{ $role }}][{{ $feature }}]"
                                                value="1"
                                                aria-label="{{ $roleLabel }}: {{ $label }}"
                                                @checked($settingsByRole[$role]->features[$feature] ?? false)
                                                class="h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-700"
                                            >
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>

        </form>
        @endif
    </div>
</x-app-layout>