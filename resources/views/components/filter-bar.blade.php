{{--
    GET search/filter form. Expects: $action (route url).
    Optional: $searchName (default 'q'), $searchLabel, $searchPlaceholder.
    Slot: additional <x-filter-select> fields rendered next to the search box.
--}}
@props([
    'action',
    'searchName' => 'q',
    'searchLabel' => 'Cari',
    'searchPlaceholder' => 'Cari...',
])

<form method="GET" action="{{ $action }}" class="mb-6 flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="min-w-[14rem] flex-1">
        <label class="mb-2 block text-sm font-medium text-slate-700" for="{{ $searchName }}">{{ $searchLabel }}</label>
        <input id="{{ $searchName }}" name="{{ $searchName }}" type="text" value="{{ request($searchName) }}" placeholder="{{ $searchPlaceholder }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
    </div>

    {{ $slot }}

    <div class="flex gap-2">
        <button type="submit" class="rounded-lg bg-brand-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">Terapkan</button>
        <a href="{{ $action }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Reset</a>
    </div>
</form>
