<nav x-data="{ open: false, profileOpen: false }" class="sticky top-0 z-40 border-b border-gray-200 bg-white shadow-sm">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        <div class="flex min-w-0 flex-1 items-center gap-5 sm:gap-8">
            <a href="{{ route('dashboard') }}" class="flex shrink-0 items-center gap-3">
                <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                <span class="font-semibold text-gray-900">Inprogres</span>
            </a>

            <div class="hidden items-center gap-2 md:flex">
                <a href="{{ route('dashboard') }}" class="rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100' }}">Dashboard</a>
                @if (Auth::user()->isAdmin())
                    <a href="{{ route('settings.roles.edit') }}" class="whitespace-nowrap rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs('settings.roles.*') ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100' }}">{{ Auth::user()->isSuperAdmin() ? 'Pengaturan tampilan role' : 'Kelola akun' }}</a>
                @endif
                @if (Auth::user()->isSuperAdmin())
                    <a href="{{ route('settings.accounts.index') }}" class="whitespace-nowrap rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs('settings.accounts.*') ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100' }}">Kelola akun</a>
                @endif
            </div>
        </div>

        <div class="hidden items-center gap-4 md:flex">
            <div class="relative" @click.outside="profileOpen = false">
                <button type="button" @click="profileOpen = !profileOpen" class="flex items-center gap-2 rounded-md px-2 py-1.5 text-left hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-500" aria-label="Menu pengguna" :aria-expanded="profileOpen.toString()">
                    <span class="text-right">
                        <span class="block max-w-40 truncate text-sm font-medium text-gray-900">{{ Auth::user()->name }}</span>
                        <span class="block text-xs text-gray-500">{{ Auth::user()->role }}</span>
                    </span>
                    <svg class="h-4 w-4 text-gray-500 transition-transform" :class="profileOpen ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 011.06 0L10 10.94l3.72-3.72a.75.75 0 111.06 1.06l-4.25 4.25a.75.75 0 01-1.06 0L5.22 8.28a.75.75 0 010-1.06z" clip-rule="evenodd" />
                    </svg>
                </button>
                <div x-show="profileOpen" x-cloak x-transition class="absolute right-0 z-50 mt-2 w-48 rounded-md border border-gray-200 bg-white py-1 shadow-lg">
                    <a href="{{ route('account.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Akun</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100">Keluar</button>
                    </form>
                </div>
            </div>
        </div>

        <button type="button" @click="open = !open" class="rounded-md p-2 text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-500 md:hidden" aria-label="Buka navigasi" :aria-expanded="open.toString()">
            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <path :class="open ? 'hidden' : 'inline-flex'" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                <path :class="open ? 'inline-flex' : 'hidden'" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <div x-show="open" x-cloak class="border-t border-gray-200 bg-white px-4 py-3 shadow-md md:hidden">
        <div class="space-y-1">
            <a href="{{ route('dashboard') }}" @click="open = false" class="block rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100' }}">Dashboard</a>
            @if (Auth::user()->isAdmin())
                <a href="{{ route('settings.roles.edit') }}" @click="open = false" class="block rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs('settings.roles.*') ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100' }}">{{ Auth::user()->isSuperAdmin() ? 'Pengaturan tampilan role' : 'Kelola akun' }}</a>
            @endif
            @if (Auth::user()->isSuperAdmin())
                <a href="{{ route('settings.accounts.index') }}" @click="open = false" class="block rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs('settings.accounts.*') ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100' }}">Kelola akun</a>
            @endif
            <a href="{{ route('account.edit') }}" @click="open = false" class="block rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">Akun</a>
        </div>
        <div class="mt-3 border-t border-gray-200 px-3 pt-3">
            <div class="relative" @click.outside="profileOpen = false">
                <button type="button" @click="profileOpen = !profileOpen" class="flex w-full items-center justify-between rounded-md px-2 py-2 text-left hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-500" aria-label="Menu pengguna" :aria-expanded="profileOpen.toString()">
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-medium text-gray-900">{{ Auth::user()->name }}</span>
                        <span class="block text-xs text-gray-500">{{ Auth::user()->role }}</span>
                    </span>
                    <svg class="h-4 w-4 shrink-0 text-gray-500 transition-transform" :class="profileOpen ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 011.06 0L10 10.94l3.72-3.72a.75.75 0 111.06 1.06l-4.25 4.25a.75.75 0 01-1.06 0L5.22 8.28a.75.75 0 010-1.06z" clip-rule="evenodd" />
                    </svg>
                </button>
                <div x-show="profileOpen" x-cloak x-transition class="mt-1 rounded-md border border-gray-200 bg-white py-1">
                    <a href="{{ route('account.edit') }}" @click="open = false" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Akun</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100">Keluar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</nav>
