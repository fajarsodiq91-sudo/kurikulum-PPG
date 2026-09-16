<nav class="mb-6 flex items-center gap-2 text-sm" aria-label="Breadcrumb">
    <a href="{{ route('dashboard') }}" class="font-medium text-slate-500 hover:text-slate-900">Dashboard</a>
    @hasSection('breadcrumb')
        <span class="text-slate-300">/</span>
        <span class="font-semibold text-slate-900">@yield('breadcrumb')</span>
    @endif
</nav>