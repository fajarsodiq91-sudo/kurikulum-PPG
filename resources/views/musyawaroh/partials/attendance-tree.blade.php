{{--
    Recursive node of the attendance hierarchy: $node (key, label, groups, children) and
    $level (0 = the user's own top node). Renders a "pilih semua" checkbox per dapukan group
    plus one checkbox per member, then recurses into the node's children.
--}}
<div class="rounded-lg border border-slate-200 p-3 {{ $level > 0 ? 'ml-4 mt-2 bg-slate-50/50' : 'mb-3' }}">
    <p class="text-sm font-semibold text-slate-700">{{ $node['label'] }}</p>

    @forelse ($node['groups'] as $groupLabel => $members)
        <div class="mt-2" data-attendance-group>
            <label class="flex items-center gap-2 text-xs font-semibold text-slate-600">
                <input type="checkbox" data-group-toggle class="h-3.5 w-3.5 rounded border-slate-300">
                {{ $groupLabel }}
            </label>
            <div class="ml-5 mt-1 grid grid-cols-1 gap-1 sm:grid-cols-2">
                @foreach ($members as $member)
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="attendee_user_ids[]" value="{{ $member->user_id }}" data-group-member class="h-3.5 w-3.5 rounded border-slate-300">
                        {{ $member->user_name }}
                        @if ($member->jabatan)
                            <span class="text-xs text-slate-400">({{ $member->jabatan }})</span>
                        @endif
                    </label>
                @endforeach
            </div>
        </div>
    @empty
        <p class="mt-1 text-xs text-slate-400">Belum ada pengurus aktif di level ini.</p>
    @endforelse

    @foreach ($node['children'] as $child)
        @include('musyawaroh.partials.attendance-tree', ['node' => $child, 'level' => $level + 1])
    @endforeach
</div>
