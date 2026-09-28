// Shared face-api helpers: lazy loading, detection options, and descriptors from photos or video.
let loading = null;

export const loadFaceApi = (modelUrl) => {
	loading ??= (async () => {
		const faceapi = await import('@vladmandic/face-api');
		await Promise.all([
			faceapi.nets.tinyFaceDetector.loadFromUri(modelUrl),
			faceapi.nets.faceLandmark68Net.loadFromUri(modelUrl),
			faceapi.nets.faceRecognitionNet.loadFromUri(modelUrl),
		]);

		return {
			faceapi,
			detector: new faceapi.TinyFaceDetectorOptions({ inputSize: 416, scoreThreshold: 0.5 }),
		};
	})();
	loading.catch(() => {
		loading = null;
	});

	return loading;
};

// Resolves to the 128-number descriptor of the single face in the image/video, or null.
export const describeFace = async ({ faceapi, detector }, source) => {
	const result = await faceapi.detectSingleFace(source, detector).withFaceLandmarks().withFaceDescriptor();

	return result ? Array.from(result.descriptor) : null;
};
