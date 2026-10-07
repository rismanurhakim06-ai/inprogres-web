<x-guest-layout title="Selamat datang kembali" description="Masuk untuk melanjutkan ke portal layanan Polmanda.">
    <x-auth-session-status class="auth-session-status" :status="session('status')" />

    <form class="auth-form" method="POST" action="{{ route('login') }}">
        @csrf

        <div class="auth-field">
            <x-input-label for="email" :value="__('Alamat email')" class="auth-label" />
            <x-text-input id="email" class="auth-input block w-full" type="email" name="email" :value="old('email')" placeholder="nama@polmanda.ac.id" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="auth-error" />
        </div>

        <div class="auth-field">
            <x-input-label for="password" :value="__('Kata sandi')" class="auth-label" />
            <x-text-input id="password" class="auth-input block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="auth-error" />
        </div>

        <div class="auth-auxiliary">
            <label for="remember_me" class="auth-remember">
                <input id="remember_me" type="checkbox" name="remember">
                <span>{{ __('Ingat saya') }}</span>
            </label>
            @if (Route::has('password.request'))
                <a class="auth-link" href="{{ route('password.request') }}">
                    {{ __('Lupa kata sandi?') }}
                </a>
            @endif
        </div>

        <x-primary-button class="auth-submit">Masuk <span aria-hidden="true">→</span></x-primary-button>
    </form>

    <div class="auth-divider"><span>ATAU</span></div>
    <p class="auth-account-prompt">Belum punya akun? <a class="auth-link" href="{{ route('register') }}">Buat akun</a></p>
</x-guest-layout>
