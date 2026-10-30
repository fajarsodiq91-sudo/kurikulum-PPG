{{--
    A single filter dropdown for use inside <x-filter-bar>. Reads its current value from the
    query string by $name, so it stays selected after the form is submitted.
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
    $selected = (string) request($name, '');
@endphp

<div class="min-w-[10rem]">
    <label class="mb-2 block text-sm font-medium text-slate-700" for="{{ $name }}">{{ $label }}</label>
    <select id="{{ $name }}" name="{{ $name }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
        <option value="">{{ $placeholder }}</option>
        @foreach ($options as $value => $label)
            @if (is_object($label))
                @php
                    $option = $label;
                    $value = $option->{$optionValue};
                    $label = $option->{$optionLabel};
                @endphp
            @endif
            <option value="{{ $value }}" @selected($selected === (string) $value)>{{ $label }}</option>
        @endforeach
    </select>
</div>
