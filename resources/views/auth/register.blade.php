<x-guest-layout title="Buat akun" description="Lengkapi data berikut untuk mendaftar ke portal layanan Polmanda.">
    <form class="auth-form auth-register-form" method="POST" action="{{ route('register') }}">
        @csrf

        <div class="auth-field">
            <x-input-label for="name" value="Nama" class="auth-label" />
            <x-text-input id="name" class="auth-input block w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="auth-error" />
        </div>

        <div class="auth-field">
            <x-input-label for="email" value="Email" class="auth-label" />
            <x-text-input id="email" class="auth-input block w-full" type="email" name="email" :value="old('email')" placeholder="nama@polmanda.ac.id" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="auth-error" />
        </div>

        <div class="auth-field">
            <x-input-label for="phone_number" value="Nomor Telepon (Terdaftar di Telegram)" class="auth-label" />
            <x-text-input id="phone_number" class="auth-input block w-full" type="tel" name="phone_number" :value="old('phone_number')" placeholder="Contoh: 08123456789" autocomplete="tel" />
            <p class="auth-help">Nomor telepon aktif yang sudah terdaftar sebagai pengguna di Telegram.</p>
            <x-input-error :messages="$errors->get('phone_number')" class="auth-error" />
        </div>

        <div class="auth-field">
            <x-input-label for="telegram_chat_id" value="ID Chat Telegram (Opsional)" class="auth-label" />
            <x-text-input id="telegram_chat_id" class="auth-input block w-full" type="text" name="telegram_chat_id" :value="old('telegram_chat_id')" placeholder="Contoh: 123456789" />
            <p class="auth-help">Ketik /start di bot Anda atau cek ID via @userinfobot di Telegram.</p>
            <x-input-error :messages="$errors->get('telegram_chat_id')" class="auth-error" />
        </div>

        <div class="auth-field">
            <x-input-label for="password" value="Kata sandi" class="auth-label" />
            <x-text-input id="password" class="auth-input block w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="auth-error" />
        </div>

        <div class="auth-field">
            <x-input-label for="password_confirmation" value="Konfirmasi kata sandi" class="auth-label" />
            <x-text-input id="password_confirmation" class="auth-input block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="auth-error" />
        </div>

        <x-primary-button class="auth-submit">Daftar <span aria-hidden="true">→</span></x-primary-button>
    </form>

    <div class="auth-divider"><span>ATAU</span></div>
    <p class="auth-account-prompt">Sudah punya akun? <a class="auth-link" href="{{ route('login') }}">Masuk</a></p>
</x-guest-layout>
