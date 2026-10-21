import { Html5Qrcode, Html5QrcodeSupportedFormats } from 'html5-qrcode';

const reader = document.getElementById('member-qr-reader');
const startButton = document.getElementById('member-scan-start');
const stopButton = document.getElementById('member-scan-stop');
const confirmButton = document.getElementById('member-scan-confirm');
const status = document.getElementById('member-scan-status');

if (reader && startButton && stopButton && confirmButton && status) {
    const scanner = new Html5Qrcode(reader.id, {
        formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE],
        verbose: false,
    });
    let processing = false;
    let pendingSignature = null;

    const sendSignature = async (url, signature) => fetch(url, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ qr_signature: signature }),
    });

    startButton.addEventListener('click', async () => {
        startButton.disabled = true;
        status.textContent = 'Meminta izin kamera...';

        try {
            await scanner.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 240, height: 240 } },
                async (decodedText) => {
                    if (processing) return;

                    processing = true;
                    status.textContent = 'Memeriksa QR pembayaran...';
                    await scanner.stop();

                    let signature = decodedText;
                    try {
                        const decodedSignature = atob(decodedText);
                        if (decodedSignature.startsWith('eyJpdiI6')) signature = decodedSignature;
                    } catch {
                        signature = decodedText;
                    }

                    try {
                        const response = await sendSignature(startButton.dataset.previewUrl, signature);
                        const result = await response.json();

                        if (!response.ok) {
                            throw new Error(result.message || 'QR tidak dapat diproses.');
                        }

                        pendingSignature = signature;
                        status.textContent = `Anggota: ${result.customer_name} · ID ${result.member_code || '-'} · Rp ${new Intl.NumberFormat('id-ID').format(result.amount)} · berlaku sampai ${new Date(result.expires_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}. Pastikan data sesuai.`;
                        startButton.textContent = 'Scan ulang';
                        startButton.disabled = false;
                        confirmButton.hidden = false;
                        stopButton.hidden = true;
                        return;
                    } catch (error) {
                        status.textContent = error.message || 'QR gagal diproses. Silakan coba lagi.';
                        processing = false;
                        startButton.disabled = false;
                        startButton.textContent = 'Mulai scan';
                        stopButton.hidden = true;
                    }
                },
                () => {},
            );
            status.textContent = 'Kamera aktif. Arahkan ke QR pembayaran.';
            startButton.textContent = 'Kamera aktif';
            stopButton.hidden = false;
        } catch {
            status.textContent = 'Kamera tidak tersedia atau izin belum diberikan. Aktifkan izin kamera lalu coba lagi.';
            startButton.disabled = false;
            startButton.textContent = 'Mulai scan';
        }
    });

    startButton.addEventListener('click', () => {
        if (!pendingSignature) return;
        pendingSignature = null;
        processing = false;
        confirmButton.hidden = true;
        status.textContent = 'Siap untuk memindai QR lain.';
    });

    confirmButton.addEventListener('click', async () => {
        if (!pendingSignature) return;

        confirmButton.disabled = true;
        status.textContent = 'Mencatat pembayaran...';

        try {
            const response = await sendSignature(startButton.dataset.scanUrl, pendingSignature);
            const result = await response.json();

            if (!response.ok) throw new Error(result.message || 'Pembayaran gagal dicatat.');

            pendingSignature = null;
            confirmButton.hidden = true;
            status.textContent = `Pembayaran berhasil · Rp ${new Intl.NumberFormat('id-ID').format(result.amount)} · streak ${result.streak_updated} hari`;
            startButton.disabled = true;
            startButton.textContent = 'Pembayaran tercatat';
            window.setTimeout(() => window.location.reload(), 2000);
        } catch (error) {
            status.textContent = error.message || 'Pembayaran gagal dicatat. Silakan coba lagi.';
            confirmButton.disabled = false;
        }
    });

    stopButton.addEventListener('click', async () => {
        if (scanner.isScanning) await scanner.stop();
        status.textContent = 'Kamera dihentikan.';
        startButton.disabled = false;
        startButton.textContent = 'Mulai scan';
        stopButton.hidden = true;
    });
}