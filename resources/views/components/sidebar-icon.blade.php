@props(['name' => 'dot'])

@php
    $icons = [
        'home' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 10.5 12 3l9 7.5M5 9.5V19a1 1 0 0 0 1 1h3v-5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v5h3a1 1 0 0 0 1-1V9.5" />',
        'clipboard-check' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 3.75h6a1 1 0 0 1 1 1V6h1.25A1.75 1.75 0 0 1 19 7.75v11.5A1.75 1.75 0 0 1 17.25 21H6.75A1.75 1.75 0 0 1 5 19.25V7.75A1.75 1.75 0 0 1 6.75 6H8V4.75a1 1 0 0 1 1-1Zm0 0V6h6V3.75M8.75 12.5l2 2 4-4.25" />',
        'user-check' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.5 11a3.25 3.25 0 1 0 0-6.5 3.25 3.25 0 0 0 0 6.5Zm-6 9c0-3.17 2.46-5.5 6-5.5 1.1 0 2.1.23 2.96.66M15.5 15l1.75 1.75L20.5 13" />',
        'cog' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25M12 18.75V21M4.5 12H6.75M17.25 12H19.5M6 6l1.6 1.6M16.4 16.4 18 18M18 6l-1.6 1.6M7.6 16.4 6 18" />',
        'academic-cap' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4 3 9l9 5 9-5-9-5Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6.5 11.5V16c0 1.38 2.46 2.5 5.5 2.5s5.5-1.12 5.5-2.5v-4.5" />',
        'pencil' => '<path stroke-linecap="round" stroke-linejoin="round" d="m4.5 19.5 1-4L15.5 5.5l3 3L8.5 18.5l-4 1Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 7.5 16.5 10.5" />',
        'users' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 11a2.75 2.75 0 1 0 0-5.5A2.75 2.75 0 0 0 9 11Zm7 1a2.25 2.25 0 1 0 0-4.5 2.25 2.25 0 0 0 0 4.5Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M3.5 19c0-3 2.46-5 5.5-5s5.5 2 5.5 5M14 19c.2-2.2 1.8-3.75 4-3.75 1.8 0 3.3 1 3.8 2.5" />',
        'grid' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 4.5h6.5V11H4V4.5Zm9.5 0H20V11h-6.5V4.5ZM4 13.5h6.5V20H4v-6.5Zm9.5 0H20V20h-6.5v-6.5Z" />',
        'database' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75c3.73 0 6.75-1.01 6.75-2.25S15.73 2.25 12 2.25 5.25 3.26 5.25 4.5 8.27 6.75 12 6.75Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 4.5v6c0 1.24 3.02 2.25 6.75 2.25s6.75-1.01 6.75-2.25v-6M5.25 10.5v6c0 1.24 3.02 2.25 6.75 2.25s6.75-1.01 6.75-2.25v-6" />',
        'book-open' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.5c-1.6-1-4-1.5-6.5-1.25A1 1 0 0 0 4.5 6.25v11.5a1 1 0 0 0 1.1 1c2.3-.2 4.6.2 6.4 1.25 1.8-1.05 4.1-1.45 6.4-1.25a1 1 0 0 0 1.1-1V6.25a1 1 0 0 0-1-1C16 5 13.6 5.5 12 6.5Zm0 0V19" />',
        'clock' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5V12l3 2M20.25 12a8.25 8.25 0 1 1-16.5 0 8.25 8.25 0 0 1 16.5 0Z" />',
        'check-circle' => '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 12a8.25 8.25 0 1 1-16.5 0 8.25 8.25 0 0 1 16.5 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="m8.5 12.3 2.3 2.3 4.7-5" />',
        'envelope' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 6.75h16v10.5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6.75Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 7 12 13l7.5-6" />',
        'chart-bar' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5v-6M9.5 19.5v-10M14.5 19.5v-4M19.5 19.5V6" />',
        'question-circle' => '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 12a8.25 8.25 0 1 1-16.5 0 8.25 8.25 0 0 1 16.5 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.5a2.25 2.25 0 1 1 3.5 1.87c-.72.46-1.25 1-1.25 1.88v.5M12 17h.01" />',
        'dot' => '<circle cx="12" cy="12" r="2.5" />',
    ];

    $path = $icons[$name] ?? $icons['dot'];
@endphp

<svg {{ $attributes->merge(['class' => 'h-4 w-4', 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.5']) }} aria-hidden="true">{!! $path !!}</svg>
