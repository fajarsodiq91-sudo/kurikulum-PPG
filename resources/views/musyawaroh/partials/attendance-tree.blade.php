{{--
    Recursive node of the attendance hierarchy: $node (key, label, groups, children), $level
    (0 = the user's own top node), and $collapsed (whether this node starts collapsed - daerah
    and desa start collapsed to avoid a wall of names, kelompok starts open). Each member gets
    a 3-way Hadir/Izin/Alpha control; the search box above the tree filters by member name and
    auto-expands any node containing a match.
--}}
<div class="rounded-lg border border-slate-200 p-3 {{ $level > 0 ? 'ml-4 mt-2 bg-slate-50/50' : 'mb-3' }}" data-attendance-node data-default-collapsed="{{ $collapsed ? '1' : '0' }}">
    <button type="button" data-node-toggle class="flex w-full items-center justify-between gap-2 text-left">
        <span class="text-sm font-semibold text-slate-700">{{ $node['label'] }}</span>
        <svg data-node-chevron class="h-4 w-4 shrink-0 text-slate-400 transition-transform {{ $collapsed ? '' : 'rotate-90' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="m9 6 6 6-6 6" />
        </svg>
    </button>

    <div data-node-body class="mt-2 {{ $collapsed ? 'hidden' : '' }}">
        @forelse ($node['groups'] as $groupLabel => $members)
            <div class="mt-2" data-attendance-group>
                <label class="flex items-center gap-2 text-xs font-semibold text-slate-600">
                    <input type="checkbox" data-group-toggle class="h-3.5 w-3.5 rounded border-slate-300">
                    {{ $groupLabel }}
                </label>
                <div class="ml-5 mt-1 space-y-1">
                    @foreach ($members as $member)
                        <div class="flex flex-wrap items-center justify-between gap-2 rounded px-1 py-1 text-sm text-slate-700" data-member-row data-member-name="{{ \Illuminate\Support\Str::lower($member->user_name) }}">
                            <span class="min-w-0 truncate">
                                {{ $member->user_name }}
                                @if ($member->jabatan)
                                    <span class="text-xs text-slate-400">({{ $member->jabatan }})</span>
                                @endif
                            </span>
                            <div class="flex shrink-0 items-center gap-1" data-attendance-control>
                                <div class="inline-flex overflow-hidden rounded-md border border-slate-300 text-[11px] font-medium">
                                    <label class="cursor-pointer px-2 py-1 has-[:checked]:bg-emerald-100 has-[:checked]:text-emerald-700">
                                        <input type="radio" name="attendance[{{ $member->user_id }}]" value="hadir" class="sr-only" data-group-member>
                                        Hadir
                                    </label>
                                    <label class="cursor-pointer border-l border-slate-300 px-2 py-1 has-[:checked]:bg-amber-100 has-[:checked]:text-amber-700">
                                        <input type="radio" name="attendance[{{ $member->user_id }}]" value="izin" class="sr-only">
                                        Izin
                                    </label>
                                    <label class="cursor-pointer border-l border-slate-300 px-2 py-1 has-[:checked]:bg-rose-100 has-[:checked]:text-rose-700">
                                        <input type="radio" name="attendance[{{ $member->user_id }}]" value="alpha" class="sr-only">
                                        Alpha
                                    </label>
                                </div>
                                <button type="button" data-clear-attendance class="px-1 text-slate-300 hover:text-slate-600" aria-label="Kosongkan">&times;</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="mt-1 text-xs text-slate-400">Belum ada pengurus aktif di level ini.</p>
        @endforelse

        @foreach ($node['children'] as $child)
            @include('musyawaroh.partials.attendance-tree', ['node' => $child, 'level' => $level + 1, 'collapsed' => $collapsed])
        @endforeach
    </div>
</div>
