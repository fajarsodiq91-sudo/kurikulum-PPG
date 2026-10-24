@props(['title', 'description' => null])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center']) }}>
    <p class="text-sm font-semibold text-slate-700">{{ $title }}</p>
    @if ($description)
        <p class="mt-1 text-xs leading-5 text-slate-500">{{ $description }}</p>
    @endif
    @if (! $slot->isEmpty())
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
