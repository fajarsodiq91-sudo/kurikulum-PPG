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
                    <p class="text-xs text-slate-500">Management & Learning Monitoring System</p>
                </div>
            </div>

            <h1 class="text-2xl font-bold text-slate-800 mb-6">Masuk ke Sistem</h1>

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
                    <input id="password" name="password" type="password" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand-500 focus:outline-none">
                </div>

                <button type="submit" class="w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                    Masuk
                </button>
            </form>

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
</body>
</html>
