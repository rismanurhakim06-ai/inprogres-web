@props([
    'title' => 'Masuk ke akun',
    'description' => 'Gunakan email dan kata sandi untuk melanjutkan ke portal layanan.',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title }} | {{ config('app.name', 'Polmanda Helpdesk') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="auth-shell" style="--auth-photo: url('{{ asset('img/desainpolmanda.jpg') }}')">
        <header class="auth-header">
            <a class="auth-brand" href="{{ route('home') }}">
                <img src="{{ asset('img/polmanda2.png') }}" alt="Polmanda">
                <span>
                    <strong>Ticket Polmanda</strong>
                    <small>HELPDESK PORTAL</small>
                </span>
            </a>
        </header>

        <main class="auth-main">
            <section class="auth-card" aria-labelledby="auth-title">
                <div class="auth-mark">
                    <img src="{{ asset('img/polmanda2.png') }}" alt="">
                </div>
                <h1 id="auth-title">{{ $title }}</h1>
                <p class="auth-description">{{ $description }}</p>
                {{ $slot }}
            </section>
        </main>

        <footer class="auth-footer">
            <span>© 2026 Politeknik Terpadu. Seluruh hak cipta dilindungi undang-undang.</span>
            <span class="auth-footer-links">Kebijakan Privasi <i>·</i> Syarat &amp; Ketentuan <i>·</i> Status Sistem</span>
        </footer>
    </body>
</html>
