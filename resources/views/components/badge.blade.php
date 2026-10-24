@props(['color' => 'slate'])

@php
    $colors = [
        'slate' => 'bg-slate-100 text-slate-600',
        'brand' => 'bg-brand-100 text-brand-700',
        'emerald' => 'bg-emerald-100 text-emerald-700',
        'amber' => 'bg-amber-100 text-amber-700',
        'rose' => 'bg-rose-100 text-rose-700',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold '.($colors[$color] ?? $colors['slate'])]) }}>
    {{ $slot }}
</span>
