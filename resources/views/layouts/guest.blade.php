<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Masuk' }} · Aplikasi Kepegawaian PIS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-gradient-to-br from-emerald-900 via-emerald-800 to-slate-900 p-4">
    <div class="w-full max-w-md">
        <div class="mb-6 text-center text-emerald-50">
            <p class="text-xs uppercase tracking-[0.3em] text-emerald-300">Pesantren Imam Syafi'i</p>
            <h1 class="mt-2 text-2xl font-semibold">Aplikasi Kepegawaian</h1>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-xl">
            @yield('content')
        </div>

        <p class="mt-4 text-center text-xs text-emerald-200">
            Gunakan akun yang diberikan administrator. Aktivitas masuk dicatat.
        </p>
    </div>
</body>
</html>
