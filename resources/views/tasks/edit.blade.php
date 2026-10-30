@extends('layouts.app')

@section('title', 'Ubah Tugas')
@section('page-title', 'Ubah Tugas')
@section('breadcrumb', 'Musyawaroh / Ubah Tugas')

@section('content')
    @php $assigneeIds = $task->assignees->pluck('id')->all(); @endphp

    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold text-amber-600">Tugas</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Ubah Tugas</h2>
        </div>
        <a href="{{ route('tasks.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Kembali ke papan tugas</a>
    </div>

    <section class="max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('tasks.update', $task) }}" enctype="multipart/form-data" class="grid gap-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-medium mb-2" for="title">Judul Tugas</label>
                <input id="title" name="title" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ old('title', $task->title) }}">
            </div>
            <div>
                <label class="block text-sm font-medium mb-2" for="description">Deskripsi / Catatan Musyawaroh</label>
                <textarea id="description" name="description" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('description', $task->description) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2" for="bucket">Kategori / Sumber (misal nama musyawaroh)</label>
                <input id="bucket" name="bucket" type="text" list="bucket-suggestions" class="w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ old('bucket', $task->bucket) }}">
                <datalist id="bucket-suggestions">
                    @foreach ($bucketSuggestions as $suggestion)
                        <option value="{{ $suggestion }}"></option>
                    @endforeach
                </datalist>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2" for="priority">Prioritas</label>
                <select id="priority" name="priority" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="low" @selected(old('priority', $task->priority) === 'low')>Rendah</option>
                    <option value="medium" @selected(old('priority', $task->priority) === 'medium')>Sedang</option>
                    <option value="high" @selected(old('priority', $task->priority) === 'high')>Tinggi</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2" for="due_date">Jatuh Tempo</label>
                <input id="due_date" name="due_date" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ old('due_date', $task->due_date?->format('Y-m-d')) }}">
            </div>
            <div>
                <label class="block text-sm font-medium mb-2" for="link">Link (opsional)</label>
                <input id="link" name="link" type="url" placeholder="https://drive.google.com/..." class="w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ old('link', $task->link) }}">
            </div>
            <div>
                <label class="block text-sm font-medium mb-2" for="attachment">Lampiran File (opsional)</label>
                @if ($task->attachment)
                    <p class="mb-2 text-xs text-slate-500">
                        Lampiran saat ini: <a href="{{ route('tasks.attachment', $task) }}" target="_blank" class="font-medium text-brand-600 hover:underline">lihat berkas</a>.
                        Unggah berkas baru di bawah untuk menggantinya.
                    </p>
                @endif
                <input id="attachment" name="attachment" type="file" class="w-full rounded-lg border border-slate-300 px-3 py-2 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-sm file:font-medium">
                <p class="mt-1 text-xs text-slate-500">PDF, Word, Excel, PowerPoint, atau gambar. Maks. 10 MB.</p>
            </div>
            <div>
                <span class="mb-2 block text-sm font-medium text-slate-700">Ditugaskan Kepada</span>
                @include('tasks.partials.assignee-picker', ['recipients' => $recipients, 'selectedIds' => old('assignees', $assigneeIds)])
                <p class="mt-1 text-xs text-slate-500">Hanya pengurus di jalur vertikal Anda (atasan dan cabang Anda sendiri) yang bisa dipilih.</p>
            </div>

            <button type="submit" class="mt-2 w-full rounded-lg bg-brand-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">Simpan Perubahan</button>
        </form>

        @if ($task->isDeletableBy(auth()->user()))
            <form method="POST" action="{{ route('tasks.destroy', $task) }}" class="mt-4 border-t border-slate-100 pt-4" onsubmit="return confirm(@js('Hapus tugas '.$task->title.'? Tindakan ini tidak dapat dibatalkan.'))">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full rounded-lg border border-rose-200 bg-white px-4 py-2.5 text-sm font-semibold text-rose-600 transition hover:bg-rose-50">Hapus Tugas</button>
            </form>
        @endif
    </section>
@endsection
