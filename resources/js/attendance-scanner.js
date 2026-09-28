// Attendance scanners (QR camera, RFID reader, face) for the session attendance sheet.
// The heavy libraries are imported lazily, only when their mode is opened.
const panel = document.querySelector('[data-attendance-scanner]');

if (panel) {
	const scanUrl = panel.dataset.scanUrl;
	const facesUrl = panel.dataset.facesUrl;
	const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
	const messageBox = panel.querySelector('[data-scan-message]');
	const modeButtons = panel.querySelectorAll('[data-scan-mode]');
	const modePanes = panel.querySelectorAll('[data-scan-pane]');
	const recentScans = new Map();
	const modes = { qr: null, rfid: null, face: null };
	let activeMode = null;

	const showMessage = (text, kind = 'info') => {
		const colors = {
			info: 'border-slate-200 bg-slate-50 text-slate-700',
			success: 'border-green-200 bg-green-50 text-green-700',
			warning: 'border-amber-200 bg-amber-50 text-amber-700',
			error: 'border-rose-200 bg-rose-50 text-rose-700',
		};
		messageBox.className = `mt-4 rounded-lg border p-3 text-sm ${colors[kind]}`;
		messageBox.textContent = text;
	};

	const markRow = (generusId) => {
		const radio = document.querySelector(`input[name="attendance[${generusId}][status]"][value="present"]`);

		if (radio) {
			radio.checked = true;
			radio.closest('tr')?.classList.add('bg-green-50');
		}
	};

	// Same code/person within a few seconds is a repeat read of the same tap, not a new scan.
	const isRepeat = (key, windowMs = 4000) => {
		const now = Date.now();

		if (now - (recentScans.get(key) ?? 0) < windowMs) {
			return true;
		}

		recentScans.set(key, now);

		return false;
	};

	const submitScan = async (payload) => {
		try {
			const response = await fetch(scanUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
				body: JSON.stringify(payload),
			});
			const data = await response.json().catch(() => ({}));

			if (!response.ok) {
				showMessage(data.message ?? 'Scan gagal diproses.', response.status === 404 ? 'warning' : 'error');

				return null;
			}

			markRow(data.generus_id);
			showMessage(data.already_present ? `${data.name} sudah tercatat hadir.` : `${data.name} tercatat hadir.`, data.already_present ? 'info' : 'success');

			return data;
		} catch {
			showMessage('Tidak dapat menghubungi server.', 'error');

			return null;
		}
	};

	const modeControllers = {
		async qr() {
			const { Html5Qrcode } = await import('html5-qrcode');
			const scanner = new Html5Qrcode('qr-reader');

			await scanner.start(
				{ facingMode: 'environment' },
				{ fps: 10, qrbox: { width: 240, height: 240 } },
				(code) => {
					if (!isRepeat(`qr:${code}`)) {
						submitScan({ method: 'qr', code });
					}
				},
				() => {},
			);
			showMessage('Arahkan QR Code pada ID card ke kamera.');

			return { stop: () => scanner.stop().then(() => scanner.clear()).catch(() => {}) };
		},

		async rfid() {
			const input = panel.querySelector('[data-rfid-input]');
			const submit = (event) => {
				if (event.key !== 'Enter') {
					return;
				}

				event.preventDefault();
				const code = input.value.trim();
				input.value = '';

				if (code !== '' && !isRepeat(`rfid:${code}`, 1500)) {
					submitScan({ method: 'rfid', code });
				}
			};
			const refocus = () => setTimeout(() => activeMode === 'rfid' && input.focus(), 50);

			input.addEventListener('keydown', submit);
			input.addEventListener('blur', refocus);
			input.focus();
			showMessage('Tempelkan kartu RFID pada pembaca.');

			return {
				stop: () => {
					input.removeEventListener('keydown', submit);
					input.removeEventListener('blur', refocus);
				},
			};
		},

		async face() {
			if (!navigator.mediaDevices?.getUserMedia) {
				throw new Error('Kamera tidak tersedia. Gunakan HTTPS atau localhost.');
			}

			showMessage('Memuat model pengenalan wajah...');
			const faceapi = await import('@vladmandic/face-api');
			const modelUrl = panel.dataset.modelUrl;
			await Promise.all([
				faceapi.nets.tinyFaceDetector.loadFromUri(modelUrl),
				faceapi.nets.faceLandmark68Net.loadFromUri(modelUrl),
				faceapi.nets.faceRecognitionNet.loadFromUri(modelUrl),
			]);

			const people = await (await fetch(facesUrl, { headers: { Accept: 'application/json' } })).json();
			const detector = new faceapi.TinyFaceDetectorOptions({ inputSize: 416, scoreThreshold: 0.5 });
			const labeled = [];
			let unreadable = 0;

			for (const [index, person] of people.entries()) {
				showMessage(`Mempelajari foto generus ${index + 1} dari ${people.length}...`);

				try {
					const photo = await faceapi.fetchImage(person.photo);
					const result = await faceapi.detectSingleFace(photo, detector).withFaceLandmarks().withFaceDescriptor();

					if (result) {
						labeled.push(new faceapi.LabeledFaceDescriptors(String(person.id), [result.descriptor]));
						continue;
					}
				} catch {
					// Falls through to count the photo as unreadable.
				}

				unreadable++;
			}

			if (labeled.length === 0) {
				throw new Error('Tidak ada foto generus yang wajahnya dapat dikenali untuk sesi ini.');
			}

			const matcher = new faceapi.FaceMatcher(labeled, 0.5);
			const video = panel.querySelector('[data-face-video]');
			const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
			video.srcObject = stream;
			await video.play();

			let running = true;
			let busy = false;
			const timer = setInterval(async () => {
				if (!running || busy) {
					return;
				}

				busy = true;

				try {
					const result = await faceapi.detectSingleFace(video, detector).withFaceLandmarks().withFaceDescriptor();

					if (result) {
						const best = matcher.findBestMatch(result.descriptor);

						if (best.label !== 'unknown' && !isRepeat(`face:${best.label}`, 6000)) {
							submitScan({ method: 'face', generus_id: Number(best.label) });
						}
					}
				} catch {
					// A dropped frame is harmless; the next tick tries again.
				}

				busy = false;
			}, 700);

			showMessage(unreadable > 0
				? `Kamera siap. ${unreadable} foto generus tidak terbaca wajahnya dan harus diisi dengan cara lain.`
				: 'Kamera siap. Hadapkan wajah ke kamera.', unreadable > 0 ? 'warning' : 'info');

			return {
				stop: () => {
					running = false;
					clearInterval(timer);
					stream.getTracks().forEach((track) => track.stop());
					video.srcObject = null;
				},
			};
		},
	};

	const stopActive = async () => {
		const previous = activeMode;
		activeMode = null;

		if (previous && modes[previous]) {
			await modes[previous].stop();
			modes[previous] = null;
		}
	};

	const selectMode = async (mode) => {
		const wasActive = activeMode === mode;
		await stopActive();

		modeButtons.forEach((button) => {
			const selected = !wasActive && button.dataset.scanMode === mode;
			button.setAttribute('aria-pressed', String(selected));
			button.classList.toggle('bg-brand-950', selected);
			button.classList.toggle('text-white', selected);
		});
		modePanes.forEach((pane) => pane.classList.toggle('hidden', wasActive || pane.dataset.scanPane !== mode));

		if (wasActive) {
			showMessage('Pemindai dimatikan.');

			return;
		}

		activeMode = mode;

		try {
			const controller = await modeControllers[mode]();

			if (activeMode === mode) {
				modes[mode] = controller;
			} else {
				await controller.stop();
			}
		} catch (error) {
			activeMode = null;
			showMessage(error?.message || 'Pemindai tidak dapat dijalankan. Periksa izin kamera.', 'error');
		}
	};

	modeButtons.forEach((button) => button.addEventListener('click', () => selectMode(button.dataset.scanMode)));
	window.addEventListener('pagehide', stopActive);
}
