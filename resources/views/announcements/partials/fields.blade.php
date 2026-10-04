@php $announcement ??= null; @endphp

<div class="mb-4">
    <label class="mb-2 block text-sm font-medium" for="title">Judul</label>
    <input id="title" name="title" type="text" required value="{{ old('title', $announcement?->title) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
</div>

<div class="mb-4">
    <label class="mb-2 block text-sm font-medium" for="body">Isi Berita</label>
    <textarea id="body" name="body" rows="6" required class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('body', $announcement?->body) }}</textarea>
</div>

<div class="mb-4">
    <label class="mb-2 block text-sm font-medium" for="image">Gambar / Foto</label>
    @if ($announcement?->image)
        <img src="{{ $announcement->imageUrl() }}" alt="{{ $announcement->title }}" class="mb-2 h-24 w-full rounded-lg object-cover">
    @endif
    <input id="image" name="image" type="file" accept="image/png,image/jpeg,image/webp" class="w-full rounded-lg border border-slate-300 px-3 py-2">
</div>

<div class="mb-4">
    <label class="mb-2 block text-sm font-medium" for="template">Template Tampilan</label>
    <select id="template" name="template" class="w-full rounded-lg border border-slate-300 px-3 py-2">
        @foreach ($templates as $value => $label)
            <option value="{{ $value }}" @selected(old('template', $announcement?->template) === $value)>{{ $label }}</option>
        @endforeach
    </select>
</div>

<div class="mb-4">
    <label class="mb-2 block text-sm font-medium" for="status">Status</label>
    <select id="status" name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2">
        <option value="published" @selected(old('status', $announcement?->status ?? 'published') === 'published')>Terbit</option>
        <option value="draft" @selected(old('status', $announcement?->status) === 'draft')>Draft</option>
    </select>
</div>
