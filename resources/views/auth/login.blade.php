<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login | PPG</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-ppg-karawang-timur.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/login-scanner.js'])
</head>
<body class="bg-slate-100">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="w-full max-w-md bg-white rounded-xl shadow-lg p-8">
            <div class="mb-6 flex items-center gap-3">
                <img src="{{ asset('images/logo-ppg-karawang-timur.png') }}" alt="Logo PPG Karawang Timur" class="h-12 w-12 object-contain">
                <div>
                    <p class="text-sm font-bold text-slate-800">PPG Karawang Timur</p>
                    <p class="text-xs text-slate-500">Bidang Pendidikan</p>
                </div>
            </div>

            <h1 class="text-2xl font-bold text-slate-800 mb-6">Masuk ke Sistem</h1>

            @if (session('success'))
                <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-2" for="login">Email atau Nomor Induk</label>
                    <input id="login" name="login" type="text" value="{{ old('login') }}" required autocomplete="username" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand-500 focus:outline-none">
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-700 mb-2" for="password">Password</label>
                    <div class="relative">
                        <input id="password" name="password" type="password" required autocomplete="current-password" class="w-full rounded-lg border border-slate-300 py-2 pl-3 pr-11 focus:border-brand-500 focus:outline-none">
                        <button type="button" data-password-toggle aria-controls="password" aria-label="Tampilkan password" aria-pressed="false" class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-500 hover:text-slate-700">
                            <svg data-icon-show xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                            <svg data-icon-hide xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="hidden h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                    Masuk
                </button>
            </form>

            <div class="mt-4 flex items-center gap-3">
                <span class="h-px flex-1 bg-slate-200"></span>
                <span class="text-xs font-medium text-slate-400">atau</span>
                <span class="h-px flex-1 bg-slate-200"></span>
            </div>

            <a href="{{ route('login.google') }}" class="mt-4 flex w-full items-center justify-center gap-2 rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                <svg class="h-5 w-5" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M23.52 12.27c0-.82-.07-1.42-.22-2.04H12v4.07h6.6c-.13 1.09-.86 2.73-2.47 3.84l-.02.15 3.59 2.78.25.02c2.28-2.1 3.57-5.2 3.57-8.82z"/>
                    <path fill="#34A853" d="M12 24c3.24 0 5.95-1.07 7.93-2.9l-3.82-2.95c-1.02.71-2.4 1.21-4.11 1.21-3.15 0-5.82-2.09-6.78-5h-3.96v2.52C2.81 21.3 7 24 12 24z"/>
                    <path fill="#FBBC05" d="M5.22 14.36A7.4 7.4 0 0 1 4.8 12c0-.82.14-1.62.39-2.36V7.12H1.24A11.9 11.9 0 0 0 0 12c0 1.92.46 3.74 1.24 5.88z"/>
                    <path fill="#EA4335" d="M12 4.75c2.25 0 3.77.97 4.63 1.78l3.38-3.3C17.94 1.2 15.24 0 12 0 7 0 2.81 2.7 1.24 6.12l3.96 3.07c.96-2.91 3.63-4.44 6.8-4.44z"/>
                </svg>
                Masuk dengan Google
            </a>

            <div class="mt-6 border-t border-slate-200 pt-6"
                data-login-scanner
                data-rfid-url="{{ route('login.rfid') }}"
                data-face-url="{{ route('login.face') }}"
                data-model-url="{{ asset('models/face-api') }}">
                <p class="text-sm font-medium text-slate-700">Atau masuk dengan kartu / wajah</p>
                <div class="mt-3 grid grid-cols-3 gap-2">
                    <button type="button" data-login-mode="qr" aria-pressed="false" class="rounded-lg border border-slate-300 px-2 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">Scan QR</button>
                    <button type="button" data-login-mode="rfid" aria-pressed="false" class="rounded-lg border border-slate-300 px-2 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">Scan RFID</button>
                    <button type="button" data-login-mode="face" aria-pressed="false" class="rounded-lg border border-slate-300 px-2 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">Scan Wajah</button>
                </div>
                <div data-login-pane="qr" class="mt-3 hidden"><div id="login-qr-reader" class="overflow-hidden rounded-lg"></div></div>
                <div data-login-pane="rfid" class="mt-3 hidden">
                    <input data-login-rfid-input type="text" autocomplete="off" aria-label="Pembaca RFID" placeholder="Tempelkan kartu..." class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div data-login-pane="face" class="mt-3 hidden">
                    <video data-login-video class="w-full rounded-lg bg-slate-900" playsinline muted></video>
                </div>
                <p data-login-message role="status" aria-live="polite" class="mt-3 hidden rounded-lg border p-3 text-sm"></p>
            </div>
        </div>
    </div>
    <script>
        document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
            const input = document.getElementById(toggle.getAttribute('aria-controls'));

            toggle.addEventListener('click', () => {
                const isVisible = input.type === 'text';

                input.type = isVisible ? 'password' : 'text';
                toggle.setAttribute('aria-pressed', String(!isVisible));
                toggle.setAttribute('aria-label', isVisible ? 'Tampilkan password' : 'Sembunyikan password');
                toggle.querySelector('[data-icon-show]').classList.toggle('hidden', !isVisible);
                toggle.querySelector('[data-icon-hide]').classList.toggle('hidden', isVisible);
            });
        });
    </script>
</body>
</html>
