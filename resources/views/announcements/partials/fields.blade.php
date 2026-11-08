@php
    $announcement ??= null;
    $defaultEventDate ??= null;
@endphp

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

<div class="mb-4">
    <label class="mb-2 block text-sm font-medium" for="event_date">Tanggal Acara (opsional, untuk event/program)</label>
    <input id="event_date" name="event_date" type="date" value="{{ old('event_date', $announcement?->event_date?->toDateString() ?? $defaultEventDate) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
    <p class="mt-1 text-xs text-slate-500">Diisi jika ini adalah event/program pada tanggal tertentu, supaya muncul di Kalender KBM dashboard.</p>
</div>

<div class="mb-4" data-recipient-picker>
    <label class="mb-2 block text-sm font-medium">Penerima Pemberitahuan Email (opsional)</label>
    <p class="mb-2 text-xs text-slate-500">Centang pengurus/kelompok yang dituju. Kosongkan untuk mengirim ke semua pengguna aktif seperti biasa.</p>
    <input type="text" data-recipient-search placeholder="Cari nama pengurus..." class="mb-3 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
    @php $oldRecipients = collect(old('recipients', $announcement?->recipients->pluck('id')->all() ?? []))->map(fn ($id) => (int) $id); @endphp
    @forelse ($tree as $node)
        @include('announcements.partials.recipient-tree', ['node' => $node, 'level' => 0])
    @empty
        <p class="text-sm text-slate-400">Tidak ada data kepengurusan yang bisa ditampilkan untuk akun Anda.</p>
    @endforelse
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const checkedIds = @json($oldRecipients ?? []);

        document.querySelectorAll('[data-recipient-picker] input[name="recipients[]"]').forEach(function (box) {
            if (checkedIds.includes(parseInt(box.value, 10))) {
                box.checked = true;
            }
        });

        document.querySelectorAll('[data-recipient-picker] [data-recipient-group]').forEach(function (group) {
            const toggle = group.querySelector('[data-group-toggle]');
            const members = group.querySelectorAll('[data-group-member]');

            function sync() {
                const checkedCount = Array.from(members).filter((m) => m.checked).length;
                toggle.checked = members.length > 0 && checkedCount === members.length;
                toggle.indeterminate = checkedCount > 0 && !toggle.checked;
            }

            toggle.addEventListener('change', function () {
                members.forEach((m) => { m.checked = toggle.checked; });
                sync();
            });

            members.forEach((m) => m.addEventListener('change', sync));
            sync();
        });

        document.querySelectorAll('[data-recipient-picker] [data-node-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                const node = button.closest('[data-recipient-node]');
                node.querySelector('[data-node-body]').classList.toggle('hidden');
                node.querySelector('[data-node-chevron]').classList.toggle('rotate-90');
            });
        });

        const search = document.querySelector('[data-recipient-search]');

        if (search) {
            search.addEventListener('input', function () {
                const query = search.value.trim().toLowerCase();

                document.querySelectorAll('[data-recipient-picker] label').forEach(function (label) {
                    const input = label.querySelector('input[name="recipients[]"]');

                    if (!input) {
                        return;
                    }

                    label.classList.toggle('hidden', query !== '' && !label.textContent.toLowerCase().includes(query));
                });
            });
        }
    });
</script>
