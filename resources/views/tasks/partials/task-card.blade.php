@php
    $meta = $statusMeta[$task->status] ?? null;
    $isOverdue = $task->due_date && $task->status !== 'done' && $task->due_date->isPast();
@endphp
<x-card
    class="task-card p-4"
    data-priority="{{ $task->priority }}"
    data-assignee-ids="{{ $task->assignees->pluck('id')->implode(',') }}"
>
    <div class="flex items-start justify-between gap-2">
        <h4 class="text-sm font-semibold text-slate-900">{{ $task->title }}</h4>
        <span class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-medium {{ $priorityMeta[$task->priority]['class'] ?? 'bg-slate-100 text-slate-600' }}">{{ $priorityMeta[$task->priority]['label'] ?? $task->priority }}</span>
    </div>
    <div class="mt-2 flex flex-wrap items-center gap-1.5">
        @if ($meta)
            <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $meta['class'] }}">{{ $meta['label'] }}</span>
        @endif
        @if ($isOverdue)
            <span class="rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-medium text-rose-700">Terlambat</span>
        @endif
    </div>
    @if ($task->description)
        <p class="mt-2 text-xs leading-5 text-slate-500">{{ $task->description }}</p>
    @endif
    @if ($task->link || $task->attachment)
        <div class="mt-2 flex flex-wrap items-center gap-2">
            @if ($task->link)
                <a href="{{ $task->link }}" target="_blank" rel="noopener" class="text-xs font-medium text-brand-600 hover:underline">🔗 Link</a>
            @endif
            @if ($task->attachment)
                <a href="{{ route('tasks.attachment', $task) }}" target="_blank" class="text-xs font-medium text-brand-600 hover:underline">📎 Lampiran</a>
            @endif
        </div>
    @endif
    <p class="mt-3 text-xs text-slate-500">Untuk: <span class="font-medium text-slate-700">{{ $task->assignees->pluck('name')->join(', ') }}</span></p>
    <p class="mt-1 text-xs text-slate-500">Dari: <span class="font-medium text-slate-700">{{ $task->assignedBy?->name ?? '-' }}</span></p>
    @if ($task->due_date)
        <p class="mt-1 text-xs {{ $isOverdue ? 'font-medium text-rose-600' : 'text-slate-500' }}">Jatuh tempo: {{ $task->due_date->translatedFormat('d M Y') }}</p>
    @endif

    <div class="mt-3 flex items-center gap-2 border-t border-slate-100 pt-3">
        @if ($task->isEditableBy($user) && $meta)
            @if ($meta['next'])
                <form method="POST" action="{{ route('tasks.update-status', $task) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="{{ $meta['next'] }}">
                    <button type="submit" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">{{ $meta['nextLabel'] }}</button>
                </form>
            @else
                <form method="POST" action="{{ route('tasks.update-status', $task) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="todo">
                    <button type="submit" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Buka kembali</button>
                </form>
            @endif
        @endif
        @if ($task->isEditableBy($user))
            <a href="{{ route('tasks.edit', $task) }}" class="ml-auto rounded-lg px-2 py-1.5 text-xs font-medium text-slate-500 hover:bg-slate-100 hover:text-slate-900">Ubah</a>
        @endif
    </div>
</x-card>
