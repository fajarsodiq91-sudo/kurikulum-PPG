@props(['code' => null])

@php
    $icons = [
        'PD' => '<circle cx="12" cy="7.5" r="3" /><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 19.5c0-2.9 2.35-5.25 5.25-5.25s5.25 2.35 5.25 5.25" /><path stroke-linecap="round" stroke-linejoin="round" d="M10.25 7.25c.2.4.6.65 1 .65s.8-.25 1-.65" />',
        'CB' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.5c-1.6-1-4-1.5-6.5-1.25A1 1 0 0 0 4.5 6.25v11.5a1 1 0 0 0 1.1 1c2.3-.2 4.6.2 6.4 1.25 1.8-1.05 4.1-1.45 6.4-1.25a1 1 0 0 0 1.1-1V6.25a1 1 0 0 0-1-1C16 5 13.6 5.5 12 6.5Zm0 0V19" />',
        'PR' => '<path stroke-linecap="round" stroke-linejoin="round" d="m4.5 19.5 1-4L15.5 5.5l3 3L8.5 18.5l-4 1Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 7.5 16.5 10.5" />',
        'RM' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4 3 9l9 5 9-5-9-5Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6.5 11.5V16c0 1.38 2.46 2.5 5.5 2.5s5.5-1.12 5.5-2.5v-4.5" />',
        'PN' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 8.5A1.5 1.5 0 0 1 5.5 7h13A1.5 1.5 0 0 1 20 8.5v9A1.5 1.5 0 0 1 18.5 19h-13A1.5 1.5 0 0 1 4 17.5v-9Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M9 7V5.75A1.75 1.75 0 0 1 10.75 4h2.5A1.75 1.75 0 0 1 15 5.75V7M4 12.5h16" />',
    ];

    $default = '<path stroke-linecap="round" stroke-linejoin="round" d="M9 11a2.75 2.75 0 1 0 0-5.5A2.75 2.75 0 0 0 9 11Zm7 1a2.25 2.25 0 1 0 0-4.5 2.25 2.25 0 0 0 0 4.5Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M3.5 19c0-3 2.46-5 5.5-5s5.5 2 5.5 5M14 19c.2-2.2 1.8-3.75 4-3.75 1.8 0 3.3 1 3.8 2.5" />';

    $path = $icons[strtoupper((string) $code)] ?? $default;
@endphp

<svg {{ $attributes->merge(['class' => 'h-4 w-4', 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.5']) }} aria-hidden="true">{!! $path !!}</svg>
