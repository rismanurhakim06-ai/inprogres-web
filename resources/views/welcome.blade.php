<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket | Pantau pengajuan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="portal-shell">
    <header class="portal-nav">
        <a href="#" style="display: flex; align-items: center; gap: 10px; text-decoration: none; margin-right: auto;">
    <img src="/img/polmanda2.png" alt="Logo" style="width: 60px; height: auto;">
    <span  style="font-weight: bold; color: #000;">Ticket</span>
</a>
        <a class="staff-link" href="{{ route('login') }}">Akses staf <span>↗</span></a>
    </header>
    <main class="portal-main">
        <section class="hero-block">
            <p class="eyebrow">PUSAT LAYANAN DIGITAL</p>
            <h1>Setiap pengajuan,<em>terlihat progresnya.</em></h1><br>
            <p class="hero-copy">Kirim pengajuan ke tim yang tepat atau pantau statusnya dengan satu nomor tiket.</p>
        </section>
        @if (session('success'))<div class="notice success">{{ session('success') }}</div>@endif
        <section class="portal-grid">
            <div class="track-panel panel-card">
                <div class="panel-kicker"><span class="step-dot">01</span> LACAK PENGAJUAN</div>
                <h2>Di mana posisi tiketmu?</h2>
                <p class="muted">Masukkan nomor tiket untuk melihat status terbaru.</p>
                <form action="{{ route('home') }}" method="GET" class="track-form">
                    <label for="ticket">Nomor tiket</label>
                    <div class="input-action"><input id="ticket" name="ticket" value="{{ request('ticket') }}" placeholder="Contoh: INP-260919-AB12C" required><button type="submit" class="icon-button" aria-label="Lacak tiket">→</button></div>
                </form>
                @if (request()->filled('ticket'))
                    @if ($ticket)<div class="ticket-result"><div class="result-head"><span>{{ $ticket->ticket_number }}</span><span class="status-pill status-{{ $ticket->status->value }}">{{ $ticket->status->label() }}</span></div><strong>{{ $ticket->requester_name }}</strong><p>{{ $ticket->description }}</p><small>Diajukan {{ $ticket->created_at->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') }} WIB · {{ $ticket->target->label() }}</small><small class="completion-date">Tanggal selesai: {{ $ticket->completed_at?->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') ?? 'Belum selesai' }} WIB</small></div>
                    @else<div class="notice error">Nomor tiket tidak ditemukan. Periksa kembali penulisannya.</div>@endif
                @endif
                @if (auth()->user()?->role === 'user')
                    <a class="new-ticket-button" href="{{ route('dashboard') }}">
                        <span>Sudah masuk?</span> Buat tiket baru di dashboard <span class="new-ticket-arrow">↗</span>
                    </a>
                @elseif (auth()->guest())
                    <a class="new-ticket-button" href="{{ route('login') }}">
                        <span>Ingin mengajukan tiket?</span> Masuk untuk membuat tiket <span class="new-ticket-arrow">↗</span>
                    </a>
                    <a class="new-ticket-button" href="{{ route('register') }}">
                        <span>Belum punya akun?</span> Daftar sebagai user <span class="new-ticket-arrow">↗</span>
                    </a>
                @else
                    <p class="new-ticket-button"><span>Pengajuan tiket baru tersedia untuk akun user.</span></p>
                @endif
            </div>
        </section>
    </main>
    <footer class="portal-footer"><span>inprogres / layanan pengajuan</span><span>Respons transparan, langkah terarah.</span></footer>
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
