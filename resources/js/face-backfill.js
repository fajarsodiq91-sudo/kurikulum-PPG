// Computes face descriptors in the browser for members whose photo has none yet, and saves them.
import { describeFace, loadFaceApi } from './face';

const panel = document.querySelector('[data-face-backfill]');

if (panel) {
	const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
	const items = JSON.parse(panel.dataset.items);
	const button = panel.querySelector('[data-face-start]');
	const progress = panel.querySelector('[data-face-progress]');
	const remaining = panel.querySelector('[data-face-remaining]');

	button.addEventListener('click', async () => {
		button.disabled = true;
		progress.textContent = 'Memuat model pengenalan wajah...';

		try {
			const engine = await loadFaceApi(panel.dataset.modelUrl);
			const { faceapi } = engine;
			const failed = [];
			let left = items.length;

			for (const [index, item] of items.entries()) {
				progress.textContent = `Memproses ${index + 1} dari ${items.length}: ${item.name}`;

				try {
					const descriptor = await describeFace(engine, await faceapi.fetchImage(item.photo));

					if (descriptor) {
						const response = await fetch(item.store, {
							method: 'POST',
							headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
							body: JSON.stringify({ descriptor: JSON.stringify(descriptor) }),
						});

						if (response.ok) {
							remaining.textContent = String(--left);
							continue;
						}
					}
				} catch {
					// Counted as failed below.
				}

				failed.push(item.name);
			}

			progress.textContent = failed.length === 0
				? 'Selesai. Semua foto berhasil diproses.'
				: `Selesai. ${failed.length} foto tidak terbaca wajahnya: ${failed.join(', ')}.`;
		} catch (error) {
			progress.textContent = error?.message || 'Gagal memproses foto.';
			button.disabled = false;
		}
	});
}
