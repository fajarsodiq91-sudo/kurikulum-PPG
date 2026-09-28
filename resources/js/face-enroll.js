// On the member forms: turns a newly chosen photo into a face descriptor for face login.
import { describeFace, loadFaceApi } from './face';

const source = document.querySelector('[data-face-source]');
const target = document.querySelector('[data-face-descriptor]');
const status = document.querySelector('[data-face-status]');

if (source && target) {
	source.addEventListener('change', async () => {
		target.value = '';
		const file = source.files?.[0];

		if (!file) {
			status.textContent = '';

			return;
		}

		status.textContent = 'Membaca wajah pada foto...';
		const url = URL.createObjectURL(file);

		try {
			const image = new Image();
			image.src = url;
			await image.decode();

			const engine = await loadFaceApi(target.dataset.modelUrl);
			const descriptor = await describeFace(engine, image);

			if (descriptor) {
				target.value = JSON.stringify(descriptor);
				status.textContent = 'Wajah terbaca. Foto ini dapat dipakai untuk scan wajah dan login wajah.';
			} else {
				status.textContent = 'Wajah tidak terdeteksi pada foto ini, sehingga scan wajah dan login wajah tidak akan bisa dipakai. Gunakan foto yang jelas dan menghadap depan.';
			}
		} catch {
			status.textContent = 'Foto tidak dapat dibaca untuk scan wajah. Foto tetap disimpan untuk ID card.';
		} finally {
			URL.revokeObjectURL(url);
		}
	});
}
