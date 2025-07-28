<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Website Analyse - BCT</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-figtree antialiased bg-slate-900 text-white">
    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-800">
        <!-- Header -->
        <header class="bg-slate-800/80 backdrop-blur-xl border-b border-slate-700/50 shadow-2xl">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center shadow-lg">
                            <span class="text-white font-bold text-sm">BCT</span>
                        </div>
                        <div>
                            <h1 class="text-lg font-semibold text-white">Website Analyse</h1>
                            <p class="text-xs text-slate-400">Black Capital Technology</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></div>
                        <span class="text-xs text-slate-400">Live Analysis</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main>
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
</body>
</html>
