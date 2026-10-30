{{--
    A checkbox dropdown for use inside <x-filter-bar>, when a filter should allow picking more
    than one value (submitted as `name[]`). Reads its current selection from the query string.
    Expects: $name, $label, $options (assoc value => label, or a collection of models).
    Optional: $optionValue/$optionLabel when $options is a collection of models (default 'id'/'name').
--}}
@props([
    'name',
    'label',
    'options' => [],
    'placeholder' => 'Semua',
    'optionValue' => 'id',
    'optionLabel' => 'name',
])

@php
    $selected = collect(request($name, []))->map(fn ($value) => (string) $value)->all();

    $normalizedOptions = collect($options)->map(function ($option, $key) use ($optionValue, $optionLabel) {
        if (is_object($option)) {
            return ['value' => (string) $option->{$optionValue}, 'label' => (string) $option->{$optionLabel}];
        }

        return ['value' => (string) $key, 'label' => (string) $option];
    })->values();

    $selectedLabels = $normalizedOptions->filter(fn ($option) => in_array($option['value'], $selected, true))->pluck('label');
@endphp

<div class="relative min-w-[12rem]" data-multiselect data-multiselect-placeholder="{{ $placeholder }}">
    <label class="mb-2 block text-sm font-medium text-slate-700">{{ $label }}</label>
    <button
        type="button"
        class="flex w-full items-center justify-between gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-left text-sm text-slate-700 focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100"
        data-multiselect-toggle
        aria-haspopup="true"
        aria-expanded="false"
    >
        <span class="truncate" data-multiselect-label>{{ $selectedLabels->isNotEmpty() ? $selectedLabels->implode(', ') : $placeholder }}</span>
        <svg class="h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
    </button>

    <div class="absolute left-0 top-full z-20 mt-2 hidden max-h-64 w-56 overflow-y-auto rounded-lg border border-slate-200 bg-white p-2 shadow-lg" data-multiselect-panel>
        @forelse ($normalizedOptions as $option)
            <label class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm text-slate-700 hover:bg-slate-50" data-multiselect-item data-multiselect-item-label="{{ $option['label'] }}">
                <input
                    type="checkbox"
                    name="{{ $name }}[]"
                    value="{{ $option['value'] }}"
                    @checked(in_array($option['value'], $selected, true))
                    class="h-4 w-4 rounded border-slate-300 text-brand-700 focus:ring-amber-200"
                    data-multiselect-option
                >
                {{ $option['label'] }}
            </label>
        @empty
            <p class="px-2 py-1.5 text-sm text-slate-400">Tidak ada pilihan</p>
        @endforelse
    </div>
</div>
