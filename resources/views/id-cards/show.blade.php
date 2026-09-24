{{--
    Printable ID card (CR80, portrait) shared by generus and teachers.
    Expects: $cardTitle, $name, $registrationNumber, $photoDataUri (nullable), $qrCode (data URI), $backUrl.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $cardTitle }} · {{ $name }} | PPG Management</title>
    @vite(['resources/css/app.css'])
    <style>
        @page { size: A4; margin: 15mm; }
        .id-card { width: 54mm; height: 85.6mm; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
    </style>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased print:bg-white">
    <div class="mx-auto flex max-w-3xl flex-wrap items-center justify-between gap-3 px-4 pt-6 print:hidden">
        <a href="{{ $backUrl }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Kembali</a>
        <button type="button" onclick="window.print()" class="inline-flex items-center justify-center rounded-lg bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">Cetak Kartu</button>
    </div>

    <main class="flex flex-wrap items-start justify-center gap-8 px-4 py-8 print:gap-[6mm] print:p-0">
        <figure class="flex flex-col items-center gap-2">
            <article class="id-card relative flex flex-col overflow-hidden rounded-[3mm] bg-white shadow-lg ring-1 ring-slate-200 print:shadow-none">
                <header class="bg-gradient-to-b from-[#0b6fae] to-[#085a8d] px-[3mm] pb-[3mm] pt-[3.5mm] text-center text-white">
                    <div class="flex items-center justify-center gap-[1.5mm]">
                        <img src="{{ asset('images/logo-ppg-karawang-timur.png') }}" alt="" class="size-[7mm] rounded-full bg-white">
                        <span class="text-left text-[1.9mm] font-semibold uppercase leading-tight tracking-wide">PPG<br>Karawang Timur</span>
                    </div>
                    <h1 class="mt-[2mm] text-[3mm] font-extrabold uppercase leading-tight tracking-wide">{{ $cardTitle }}</h1>
                </header>
                <div class="flex h-[1mm]">
                    <span class="flex-1 bg-[#1e9a4a]"></span>
                    <span class="flex-1 bg-[#f2b12c]"></span>
                    <span class="flex-1 bg-[#e1e333]"></span>
                    <span class="flex-1 bg-[#2f7dc0]"></span>
                </div>

                <div class="flex flex-1 flex-col items-center px-[3mm] pt-[3mm] text-center">
                    <div class="h-[30mm] w-[22.5mm] overflow-hidden rounded-[1.5mm] bg-slate-100 ring-[0.6mm] ring-[#0b6fae]">
                        @if ($photoDataUri)
                            <img src="{{ $photoDataUri }}" alt="Foto {{ $name }}" class="size-full object-cover">
                        @else
                            <div class="flex size-full items-center justify-center text-[8mm] font-bold text-slate-300">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($name, 0, 1)) }}</div>
                        @endif
                    </div>
                    <p class="mt-[2mm] line-clamp-2 text-[2.9mm] font-bold uppercase leading-tight text-slate-900">{{ $name }}</p>

                    <img src="{{ $qrCode }}" alt="QR Code nomor induk {{ $registrationNumber }}" class="mt-auto size-[20mm]">
                    <p class="mb-[2.5mm] font-mono text-[2.4mm] font-semibold tracking-wider text-slate-700">{{ $registrationNumber }}</p>
                </div>
            </article>
            <figcaption class="text-xs font-medium text-slate-500 print:hidden">Depan</figcaption>
        </figure>

        <figure class="flex flex-col items-center gap-2">
            <article class="id-card relative flex flex-col overflow-hidden rounded-[3mm] bg-white shadow-lg ring-1 ring-slate-200 print:shadow-none">
                <div class="flex flex-1 items-center justify-center p-[5mm]">
                    <img src="{{ asset('images/logo-ppg-karawang-timur.png') }}" alt="Logo PPG Karawang Timur" class="w-full">
                </div>
                <div class="flex h-[2.5mm]">
                    <span class="flex-1 bg-[#1e9a4a]"></span>
                    <span class="flex-1 bg-[#f2b12c]"></span>
                    <span class="flex-1 bg-[#e1e333]"></span>
                    <span class="flex-1 bg-[#2f7dc0]"></span>
                </div>
            </article>
            <figcaption class="text-xs font-medium text-slate-500 print:hidden">Belakang</figcaption>
        </figure>
    </main>
</body>
</html>
