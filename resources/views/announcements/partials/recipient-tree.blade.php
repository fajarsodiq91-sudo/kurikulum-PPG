{{--
    Recursive node of the audience hierarchy (reuses MusyawarohHierarchy's tree): $node (key,
    label, groups, children), $level. Every member gets a plain checkbox named recipients[];
    checking a group's heading checkbox ticks everyone in it. Leaving everything unchecked
    means "everyone" (the original, un-targeted announcement behaviour).
--}}
<div class="rounded-lg border border-slate-200 p-3 {{ $level > 0 ? 'ml-4 mt-2 bg-slate-50/50' : 'mb-3' }}" data-recipient-node>
    <button type="button" data-node-toggle class="flex w-full items-center justify-between gap-2 text-left">
        <span class="text-sm font-semibold text-slate-700">{{ $node['label'] }}</span>
        <svg data-node-chevron class="h-4 w-4 shrink-0 rotate-90 text-slate-400 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="m9 6 6 6-6 6" />
        </svg>
    </button>

    <div data-node-body class="mt-2">
        @forelse ($node['groups'] as $groupLabel => $members)
            <div class="mt-2" data-recipient-group>
                <label class="flex items-center gap-2 text-xs font-semibold text-slate-600">
                    <input type="checkbox" data-group-toggle class="h-3.5 w-3.5 rounded border-slate-300">
                    {{ $groupLabel }}
                </label>
                <div class="ml-5 mt-1 space-y-1">
                    @foreach ($members as $member)
                        <label class="flex items-center gap-2 rounded px-1 py-1 text-sm text-slate-700">
                            <input type="checkbox" name="recipients[]" value="{{ $member->user_id }}" data-group-member class="h-3.5 w-3.5 rounded border-slate-300">
                            <span class="min-w-0 truncate">
                                {{ $member->user_name }}
                                @if ($member->jabatan)
                                    <span class="text-xs text-slate-400">({{ $member->jabatan }})</span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="mt-1 text-xs text-slate-400">Belum ada pengurus aktif di level ini.</p>
        @endforelse

        @foreach ($node['children'] as $child)
            @include('announcements.partials.recipient-tree', ['node' => $child, 'level' => $level + 1])
        @endforeach
    </div>
</div>
