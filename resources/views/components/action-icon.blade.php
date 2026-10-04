@props(['href' => null, 'label', 'color' => 'slate'])

@php
    $colors = [
        'slate' => 'text-slate-500 hover:bg-slate-100 hover:text-slate-900',
        'amber' => 'text-amber-600 hover:bg-amber-50 hover:text-amber-700',
        'rose' => 'text-rose-600 hover:bg-rose-50 hover:text-rose-700',
        'brand' => 'text-brand-700 hover:bg-brand-50 hover:text-brand-900',
    ];
    $classes = 'group relative inline-flex h-8 w-8 items-center justify-center rounded-lg transition '.($colors[$color] ?? $colors['slate']);
@endphp

@if ($href)
<a href="{{ $href }}" title="{{ $label }}" aria-label="{{ $label }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
    <span class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-2 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs font-medium text-white opacity-0 shadow-lg transition duration-150 group-hover:opacity-100">{{ $label }}</span>
</a>
@else
<button title="{{ $label }}" aria-label="{{ $label }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
    <span class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-2 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs font-medium text-white opacity-0 shadow-lg transition duration-150 group-hover:opacity-100">{{ $label }}</span>
</button>
@endif
