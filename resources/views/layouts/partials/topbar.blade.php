<header class="sticky top-0 z-20 flex h-20 items-center justify-between border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6 lg:px-8">
    <div class="flex items-center gap-3">
        <button type="button" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 lg:hidden" data-sidebar-toggle aria-controls="app-sidebar" aria-expanded="false">Menu</button>
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">PPG Management & Learning Monitoring System</p>
            <h1 class="mt-1 text-lg font-bold text-slate-900">@yield('page-title', 'Dashboard')</h1>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <div class="hidden text-right sm:block">
            <p class="text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</p>
            <p class="text-xs text-slate-500">{{ auth()->user()->roles->first()?->name ?? 'Pengguna' }}</p>
        </div>
        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-900 text-sm font-bold text-white" aria-hidden="true">
            {{ str($currentUser = auth()->user()->name)->substr(0, 1)->upper() }}
        </div>
    </div>
</header>