<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | PPG Management</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <div class="min-h-screen lg:flex">
        <div id="sidebar-backdrop" class="fixed inset-0 z-30 hidden bg-slate-950/40 lg:hidden" data-sidebar-close></div>

        <aside id="app-sidebar" class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col border-r border-slate-800 bg-slate-950 text-slate-300 transition-transform duration-200 lg:static lg:translate-x-0">
            @include('layouts.partials.sidebar')
        </aside>

        <div class="flex min-h-screen min-w-0 flex-1 flex-col">
            @include('layouts.partials.topbar')

            <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                @include('layouts.partials.breadcrumb')
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>