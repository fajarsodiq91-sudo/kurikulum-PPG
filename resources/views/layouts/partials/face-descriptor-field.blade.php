{{-- Fills face_descriptor from the chosen photo (computed in the browser) for face login. --}}
<input type="hidden" name="face_descriptor" data-face-descriptor data-model-url="{{ asset('models/face-api') }}">
<p class="mt-1 text-xs text-slate-500" data-face-status role="status" aria-live="polite"></p>
@vite('resources/js/face-enroll.js')
