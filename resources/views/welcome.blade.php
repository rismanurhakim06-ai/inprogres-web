<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket | Pantau pengajuan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="portal-shell" style="--portal-photo: url('{{ asset('img/desainpolmanda.jpg') }}')">
    <x-flash-toast />
    <header class="portal-nav">
        <a class="portal-brand" href="{{ route('home') }}">
            <img src="{{ asset('img/polmanda2.png') }}" alt="Polmanda">
            <span class="portal-brand-copy">
                <strong>Ticket Polmanda</strong>
                <small>PUSAT LAYANAN TERPADU</small>
            </span>
        </a>
        <a class="staff-link" href="{{ route('login') }}">Masuk Akun <span aria-hidden="true">→</span></a>
    </header>
    <main class="portal-main">
        <section class="hero-block">
            <h1>Setiap pengajuan, <em>terlihat progresnya.</em></h1>
            <p class="hero-copy">Kirim pengajuan ke tim yang tepat atau pantau statusnya dengan satu nomor tiket.</p>
        </section>
        <section class="portal-grid">
            <div class="track-panel panel-card">
                <div class="panel-kicker"><span class="step-dot">01</span> LACAK PENGAJUAN</div>
                <h2>Di mana posisi tiketmu?</h2>
                <p class="muted">Masukkan nomor tiket untuk melihat status terbaru.</p>
                <form action="{{ route('home') }}" method="GET" class="track-form">
                    <label for="ticket">Nomor tiket</label>
                    <div class="input-action">
                        <input id="ticket" name="ticket" value="{{ request('ticket') }}" placeholder="Contoh: INP-260919-AB12C" required>
                        <button type="submit" class="icon-button" aria-label="Lacak tiket">Lacak <span aria-hidden="true">→</span></button>
                    </div>
                </form>
                @if (request()->filled('ticket'))
                    @if ($ticket)<div class="ticket-result"><div class="result-head"><span>{{ $ticket->ticket_number }}</span><span class="status-pill status-{{ $ticket->status->value }}">{{ $ticket->status->label() }}</span></div><strong>{{ $ticket->requester_name }}</strong><p>{{ $ticket->description }}</p><small>Diajukan {{ $ticket->created_at->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') }} WIB · {{ $ticket->targetLabel() }}</small><small class="completion-date">Tanggal selesai: {{ $ticket->completed_at?->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') ?? 'Belum selesai' }} WIB</small></div>
                    @else<div class="notice error">Nomor tiket tidak ditemukan. Periksa kembali penulisannya.</div>@endif
                @endif
                <div class="portal-actions">
                @if (auth()->user()?->role === 'user')
                    <a class="new-ticket-button" href="{{ route('dashboard') }}">
                        <span class="new-ticket-prompt">Sudah masuk?</span>
                        <span class="new-ticket-target">Buat tiket baru di dashboard <span class="new-ticket-arrow" aria-hidden="true">↗</span></span>
                    </a>
                @elseif (auth()->guest())
                    <a class="new-ticket-button" href="{{ route('login') }}">
                        <span class="new-ticket-prompt">Ingin mengajukan tiket?</span>
                        <span class="new-ticket-target">Masuk untuk membuat tiket <span class="new-ticket-arrow" aria-hidden="true">↗</span></span>
                    </a>
                    <a class="new-ticket-button" href="{{ route('register') }}">
                        <span class="new-ticket-prompt">Belum punya akun?</span>
                        <span class="new-ticket-target">Registrasi Akun <span class="new-ticket-arrow" aria-hidden="true">↗</span></span>
                    </a>
                @else
                    <p class="new-ticket-button"><span class="new-ticket-prompt">Pengajuan tiket baru tersedia untuk akun user.</span></p>
                @endif
                </div>
            </div>
        </section>
    </main>
    <footer class="portal-footer">
        <span class="service-status"><span aria-hidden="true"></span>Semua Layanan Sistem Berjalan Normal</span>
        <span class="footer-copyright">© 2026 Politeknik Terpadu. Seluruh hak cipta dilindungi undang-undang.</span>
        <span class="footer-links">Kebijakan Privasi <span>·</span> Syarat &amp; Ketentuan <span>·</span> Pusat Bantuan</span>
    </footer>
    <script>
        document.querySelectorAll('.ticket-result, .track-panel > .notice.error').forEach((response) => {
            window.setTimeout(() => {
                response.classList.add('response-dismissed');
                window.setTimeout(() => response.remove(), 220);
            }, 5000);
        });

    </script>
</body>
</html>
