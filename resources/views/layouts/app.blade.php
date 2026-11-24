<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Aplikasi Kepegawaian PIS' }}</title>
    <link rel="manifest" href="/manifest.json">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        if ('serviceWorker' in navigator) { navigator.serviceWorker.register('/sw.js'); }
    </script>
    <style>body { font-family: 'Inter', system-ui, -apple-system, sans-serif; }</style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full text-slate-600 antialiased">
@php($u = auth()->user())
<div class="flex min-h-full">
    <!-- Mobile overlay -->
    <div id="sidebar-overlay" class="fixed inset-0 z-40 hidden bg-slate-900/50 backdrop-blur-sm md:hidden" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col border-r border-slate-200/80 bg-white transition-transform duration-300 md:static md:translate-x-0">
        <div class="flex h-16 shrink-0 items-center gap-3 border-b border-slate-100 px-6">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-600 text-white shadow-sm shadow-emerald-200">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <div>
                <p class="text-sm font-bold tracking-tight text-slate-900">PIS HRIS</p>
                <p class="text-[11px] font-medium text-slate-400">Pesantren Imam Syafi'i</p>
            </div>
            <button onclick="toggleSidebar()" class="ml-auto rounded-md p-1 text-slate-400 hover:bg-slate-100 lg:hidden">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-4 py-5 text-sm">
            @include('layouts._nav')
        </nav>

        <div class="border-t border-slate-100 p-4">
            <div class="flex items-center gap-3 rounded-xl bg-slate-50 p-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-700">
                    {{ strtoupper(substr($u->name, 0, 1)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-slate-800">{{ $u->name }}</p>
                    <p class="truncate text-[11px] text-slate-500">{{ $u->roles->pluck('name')->join(', ') ?: 'User' }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600" title="Keluar">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex min-w-0 flex-1 flex-col lg:pl-0">
        <!-- Topbar -->
        <header class="sticky top-0 z-30 flex h-16 shrink-0 items-center gap-4 border-b border-slate-200/80 bg-white/80 px-4 backdrop-blur-md sm:px-6 lg:px-8">
            <button onclick="toggleSidebar()" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 md:hidden">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <div class="min-w-0 flex-1">
                <h1 class="truncate text-lg font-bold tracking-tight text-slate-900">{{ $heading ?? ($title ?? 'Dasbor') }}</h1>
                @isset($subheading)
                    <p class="truncate text-xs text-slate-500">{{ $subheading }}</p>
                @endisset
            </div>
            <div class="flex items-center gap-3">
                <span class="hidden text-xs font-medium text-slate-400 sm:block">{{ now('Asia/Jakarta')->translatedFormat('l, d F Y') }}</span>
                <a href="{{ route('notifications.index') }}" class="relative rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
                    @php($unreadCount = \App\Models\Notification::query()->where('user_id', $u->id)->whereNull('read_at')->count())
                    @if($unreadCount > 0)
                        <span class="absolute right-1.5 top-1.5 flex h-2 w-2"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-rose-400 opacity-75"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-rose-500"></span></span>
                    @endif
                </a>
                <a href="{{ route('profile.show') }}" class="hidden rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-200 sm:block">Profil</a>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1 px-4 py-8 sm:px-6 lg:px-8">
            @include('partials.flash')
            @yield('content')
        </main>

        <footer class="border-t border-slate-200/80 bg-white px-4 py-4 text-center text-xs text-slate-400 sm:px-6 lg:px-8">
            &copy; {{ date('Y') }} Aplikasi Kepegawaian PIS &middot; Pesantren Imam Syafi'i
        </footer>
    </div>
</div>
<script>
function initSidebar() {
    const sb = document.getElementById('sidebar');
    const ov = document.getElementById('sidebar-overlay');
    if (window.innerWidth < 1024) {
        sb.style.transform = 'translateX(-100%)';
        ov.classList.add('hidden');
    } else {
        sb.style.transform = 'translateX(0)';
        ov.classList.add('hidden');
    }
}
function toggleSidebar() {
    const sb = document.getElementById('sidebar');
    const ov = document.getElementById('sidebar-overlay');
    if (window.innerWidth < 1024) {
        const hidden = sb.style.transform.includes('-100%');
        sb.style.transform = hidden ? 'translateX(0)' : 'translateX(-100%)';
        ov.classList.toggle('hidden', !hidden);
    }
}
initSidebar();
window.addEventListener('resize', initSidebar);
</script>
@stack('scripts')
</body>
</html>
