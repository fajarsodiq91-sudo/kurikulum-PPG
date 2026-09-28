{{-- Export/import XLSX bar. Expects: $exportUrl, $importUrl. The exported file doubles as the import template. --}}
<div class="mb-6 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
    <p class="text-sm text-slate-500">Export data ke XLSX, ubah di Excel, lalu import kembali dengan file yang sama. Data dengan kunci yang sama akan diperbarui, yang baru ditambahkan.</p>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <a href="{{ $exportUrl }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Export XLSX</a>
        <form method="POST" action="{{ $importUrl }}" enctype="multipart/form-data" class="flex flex-col gap-2 sm:flex-row sm:items-center">
            @csrf
            <input name="file" type="file" required accept=".xlsx" class="text-sm file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-slate-700">
            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-600">Import XLSX</button>
        </form>
    </div>
</div>
