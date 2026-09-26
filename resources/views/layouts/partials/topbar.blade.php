<header class="sticky top-0 z-20 flex h-20 items-center justify-between border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6 lg:px-8">
    <div class="flex items-center gap-3">
        <button type="button" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 lg:hidden" data-sidebar-toggle aria-controls="app-sidebar" aria-expanded="false">Menu</button>
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">PPG Management & Learning Monitoring System</p>
            <h1 class="mt-1 text-lg font-bold text-slate-900">@yield('page-title', 'Dashboard')</h1>
        </div>
    </div>

    <div class="relative">
        <button type="button" class="flex items-center gap-3 rounded-lg px-2 py-1.5 transition hover:bg-slate-100" data-profile-toggle aria-haspopup="true" aria-expanded="false" aria-controls="profile-menu">
            <div class="hidden text-right sm:block">
                <p class="text-sm font-semibold text-slate-800">{{ auth()->user()->name ?? 'Tamu' }}</p>
                <p class="text-xs text-slate-500">{{ \App\Support\Access::currentRoleLabel() }}</p>
            </div>
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-900 text-sm font-bold text-white" aria-hidden="true">
                {{ str(auth()->user()->name ?? 'Tamu')->substr(0, 1)->upper() }}
            </div>
        </button>

        <div id="profile-menu" class="absolute right-0 top-full z-30 mt-2 hidden w-52 rounded-xl border border-slate-200 bg-white p-2 shadow-lg" data-profile-menu>
            <div class="px-3 py-2 sm:hidden">
                <p class="text-sm font-semibold text-slate-800">{{ auth()->user()->name ?? 'Tamu' }}</p>
                <p class="text-xs text-slate-500">{{ \App\Support\Access::currentRoleLabel() }}</p>
            </div>
            @auth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-sm text-slate-600 transition hover:bg-slate-100">Keluar dari sistem</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="block w-full rounded-lg px-3 py-2 text-left text-sm font-semibold text-amber-600 transition hover:bg-amber-50">Masuk untuk akses penuh</a>
            @endauth
        </div>
    </div>
</header>