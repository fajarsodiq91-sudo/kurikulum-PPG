// Login by QR code (fills the number, the password is still typed), RFID card, or face (no password).
import { describeFace, loadFaceApi } from './face';

const panel = document.querySelector('[data-login-scanner]');

if (panel) {
	const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
	const message = panel.querySelector('[data-login-message]');
	const buttons = panel.querySelectorAll('[data-login-mode]');
	const panes = panel.querySelectorAll('[data-login-pane]');
	let active = null;
	let stopActive = async () => {};

	const show = (text, kind = 'info') => {
		const colors = {
			info: 'border-slate-200 bg-slate-50 text-slate-700',
			error: 'border-red-200 bg-red-50 text-red-700',
			success: 'border-green-200 bg-green-50 text-green-700',
		};
		message.className = `mt-3 rounded-lg border p-3 text-sm ${colors[kind]}`;
		message.textContent = text;
	};

	// Returns true once signed in (and navigating away), false on a rejected attempt.
	const post = async (url, body) => {
		try {
			const response = await fetch(url, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
				body: JSON.stringify(body),
			});
			const data = await response.json().catch(() => ({}));

			if (response.ok && data.redirect) {
				show('Berhasil masuk...', 'success');
				window.location.href = data.redirect;

				return true;
			}

			show(response.status === 429 ? 'Terlalu banyak percobaan. Coba lagi sebentar.' : (data.message ?? 'Login gagal.'), 'error');
		} catch {
			show('Tidak dapat menghubungi server.', 'error');
		}

		return false;
	};

	const starters = {
		async qr() {
			const { Html5Qrcode } = await import('html5-qrcode');
			const scanner = new Html5Qrcode('login-qr-reader');
			let done = false;

			await scanner.start({ facingMode: 'environment' }, { fps: 10, qrbox: { width: 220, height: 220 } }, (code) => {
				if (done) {
					return;
				}

				done = true;
				document.querySelector('#login').value = code.trim();
				show('Nomor induk terisi. Masukkan password lalu klik Masuk.', 'success');
				selectMode('qr');
				document.querySelector('#password').focus();
			}, () => {});
			show('Arahkan QR Code pada ID card ke kamera.');

			return () => scanner.stop().then(() => scanner.clear()).catch(() => {});
		},

		async rfid() {
			const input = panel.querySelector('[data-login-rfid-input]');
			let busy = false;
			const onKey = async (event) => {
				if (event.key !== 'Enter') {
					return;
				}

				event.preventDefault();
				const uid = input.value.trim();
				input.value = '';

				if (uid !== '' && !busy) {
					busy = true;
					await post(panel.dataset.rfidUrl, { uid });
					busy = false;
				}
			};
			const refocus = () => setTimeout(() => active === 'rfid' && input.focus(), 50);

			input.addEventListener('keydown', onKey);
			input.addEventListener('blur', refocus);
			input.focus();
			show('Tempelkan kartu RFID pada pembaca.');

			return () => {
				input.removeEventListener('keydown', onKey);
				input.removeEventListener('blur', refocus);
			};
		},

		async face() {
			if (!navigator.mediaDevices?.getUserMedia) {
				throw new Error('Kamera tidak tersedia. Gunakan HTTPS atau localhost.');
			}

			show('Memuat pengenalan wajah...');
			const engine = await loadFaceApi(panel.dataset.modelUrl);
			const video = panel.querySelector('[data-login-video]');
			const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
			video.srcObject = stream;
			await video.play();
			show('Hadapkan wajah ke kamera.');

			let running = true;
			let busy = false;
			let lastAttempt = 0;
			const timer = setInterval(async () => {
				if (!running || busy || Date.now() - lastAttempt < 2500) {
					return;
				}

				busy = true;

				try {
					const descriptor = await describeFace(engine, video);

					if (descriptor) {
						lastAttempt = Date.now();
						await post(panel.dataset.faceUrl, { descriptor: JSON.stringify(descriptor) });
					}
				} catch {
					// A dropped frame is harmless; the next tick tries again.
				}

				busy = false;
			}, 600);

			return () => {
				running = false;
				clearInterval(timer);
				stream.getTracks().forEach((track) => track.stop());
				video.srcObject = null;
			};
		},
	};

	async function selectMode(mode) {
		const wasActive = active === mode;
		active = null;
		await stopActive();
		stopActive = async () => {};

		buttons.forEach((button) => {
			const selected = !wasActive && button.dataset.loginMode === mode;
			button.setAttribute('aria-pressed', String(selected));
			button.classList.toggle('bg-brand-950', selected);
			button.classList.toggle('text-white', selected);
		});
		panes.forEach((pane) => pane.classList.toggle('hidden', wasActive || pane.dataset.loginPane !== mode));

		if (wasActive) {
			message.className = 'hidden';

			return;
		}

		active = mode;

		try {
			const stop = await starters[mode]();

			if (active === mode) {
				stopActive = async () => stop();
			} else {
				await stop();
			}
		} catch (error) {
			active = null;
			show(error?.message || 'Pemindai tidak dapat dijalankan. Periksa izin kamera.', 'error');
		}
	}

	buttons.forEach((button) => button.addEventListener('click', () => selectMode(button.dataset.loginMode)));
	window.addEventListener('pagehide', () => stopActive());
}
