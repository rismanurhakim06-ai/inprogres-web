<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>inprogres | Pantau pengajuan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="portal-shell">
    <header class="portal-nav">
        <a class="brand" href="{{ route('home') }}"><span class="brand-mark">i</span> inprogres</a>
        <a class="staff-link" href="{{ route('login') }}">Akses staf <span>↗</span></a>
    </header>
    <main class="portal-main">
        <section class="hero-block">
            <p class="eyebrow">PUSAT LAYANAN DIGITAL</p>
            <h1>Setiap pengajuan,<br><em>terlihat progresnya.</em></h1>
            <p class="hero-copy">Kirim pengajuan ke tim yang tepat atau pantau statusnya dengan satu nomor tiket.</p>
        </section>
        @if (session('success'))<div class="notice success">{{ session('success') }}</div>@endif
        @if (session('created_ticket'))
            <section class="ticket-confirmation" aria-live="polite">
                <div>
                    <p class="eyebrow">NOMOR TIKET ANDA</p>
                    <strong id="created-ticket-number">{{ session('created_ticket') }}</strong>
                    <p class="confirmation-copy">Simpan nomor ini untuk memantau pengajuan Anda kapan saja.</p>
                </div>
                <button type="button" class="copy-ticket-button" data-copy-ticket="{{ session('created_ticket') }}" aria-label="Salin nomor tiket">
                    <span class="copy-icon">□</span> <span data-copy-label>Salin tiket</span>
                </button>
            </section>
        @endif
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
            </div>
            <div class="create-panel panel-card">
                <div class="panel-kicker"><span class="step-dot">02</span> BUAT PENGAJUAN</div>
                <h2>Butuh bantuan?</h2><p class="muted">Ceritakan kebutuhanmu. Kami akan memberi nomor tiket untuk dipantau.</p>
                <form action="{{ route('tickets.store') }}" method="POST" class="ticket-form">
                    @csrf
                    <div class="form-row"><div><label for="requester_name">Nama pengaju</label><input id="requester_name" name="requester_name" value="{{ old('requester_name') }}" required></div><div><label for="whatsapp_number">No. WhatsApp</label><input id="whatsapp_number" name="whatsapp_number" value="{{ old('whatsapp_number') }}" placeholder="08xxxxxxxxxx" required></div></div>
                    <label for="description">Isi ajuan</label><textarea id="description" name="description" rows="4" placeholder="Jelaskan masalah atau kebutuhanmu..." required>{{ old('description') }}</textarea>
                    <div class="form-row"><div><label for="priority">Urgensi</label><select id="priority" name="priority" required><option value="">Pilih urgensi</option><option value="relaxed">Santai</option><option value="urgent">Mendesak</option><option value="critical">Urgent</option></select></div><div><label for="target">Target sistem</label><select id="target" name="target" required><option value="">Pilih sistem</option><option value="lppm">Web LPPM</option><option value="lpm">Web LPM</option><option value="ma">Web MA</option><option value="trpl">Web TRPL</option><option value="bk">Web BK</option></select></div></div>
                    @if ($errors->any())<div class="notice error">{{ $errors->first() }}</div>@endif
                    <button class="primary-button" type="submit">Kirim pengajuan <span>↗</span></button>
                </form>
            </div>
        </section>
    </main>
    <footer class="portal-footer"><span>inprogres / layanan pengajuan</span><span>Respons transparan, langkah terarah.</span></footer>
    <script>
        async function copyText(text) {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(text);

                return true;
            }

            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.setAttribute('readonly', '');
            textArea.style.position = 'fixed';
            textArea.style.opacity = '0';
            document.body.appendChild(textArea);
            textArea.select();

            let copied = false;

            try {
                copied = document.execCommand('copy');
            } finally {
                textArea.remove();
            }

            return copied;
        }

        document.querySelectorAll('[data-copy-ticket]').forEach((button) => {
            button.addEventListener('click', async () => {
                const label = button.querySelector('[data-copy-label]');

                try {
                    const copied = await copyText(button.dataset.copyTicket);

                    label.textContent = copied ? 'Tersalin' : 'Gagal menyalin';
                } catch {
                    label.textContent = 'Gagal menyalin';
                }

                window.setTimeout(() => { label.textContent = 'Salin tiket'; }, 1800);
            });
        });
    </script>
</body>
</html>
