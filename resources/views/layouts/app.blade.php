<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Daily')</title>
    <script>
        (() => {
            const stored = localStorage.getItem('theme');
            const dark = stored ? stored === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-100 text-slate-800 antialiased dark:bg-slate-950 dark:text-slate-200">
    <div class="min-h-full">
        <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/90 backdrop-blur dark:border-slate-800 dark:bg-slate-900/90">
            <div class="mx-auto flex max-w-7xl items-center gap-6 px-4 py-3 sm:px-6">
                <a href="{{ route('focus') }}" class="flex items-center gap-2 font-semibold text-slate-900 dark:text-slate-100">
                    <span class="grid h-8 w-8 place-items-center rounded-lg bg-indigo-600 text-sm font-bold text-white">D</span>
                    Daily
                </a>

                <nav class="flex items-center gap-1 text-sm font-medium">
                    @php
                        $nav = [
                            ['route' => 'focus', 'label' => 'Focus'],
                            ['route' => 'board', 'label' => 'Board'],
                            ['route' => 'projects.index', 'label' => 'Projects'],
                            ['route' => 'archive', 'label' => 'Archive'],
                        ];
                    @endphp
                    @foreach ($nav as $item)
                        @php $active = request()->routeIs(str_replace('.index', '.*', $item['route'])); @endphp
                        <a href="{{ route($item['route']) }}"
                           class="rounded-lg px-3 py-1.5 transition {{ $active ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100' }}">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>

                <div class="ml-auto flex items-center gap-3">
                    <div class="hidden text-sm text-slate-500 sm:block dark:text-slate-400">{{ now()->format('D j M Y') }}</div>

                    <button type="button" x-data="themeToggle()" @click="toggle()"
                            :title="dark ? 'Switch to light mode' : 'Switch to dark mode'"
                            :aria-label="dark ? 'Switch to light mode' : 'Switch to dark mode'"
                            :aria-pressed="dark"
                            class="rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100">
                        <svg x-show="!dark" x-cloak class="h-4.5 w-4.5" style="height:1.125rem;width:1.125rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
                        </svg>
                        <svg x-show="dark" x-cloak class="h-4.5 w-4.5" style="height:1.125rem;width:1.125rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1.5m0 15V21m9-9h-1.5m-15 0H3m15.36-6.36l-1.06 1.06M6.7 17.3l-1.06 1.06m12.72 0l-1.06-1.06M6.7 6.7L5.64 5.64M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </button>
                </div>
            </div>
        </header>

        @if (session('status'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3500)" x-cloak
                 class="mx-auto mt-4 flex max-w-7xl items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
                <span>{{ session('status') }}</span>
                <button type="button" @click="show = false" class="ml-auto text-emerald-600 hover:text-emerald-900 dark:text-emerald-400 dark:hover:text-emerald-200">&times;</button>
            </div>
        @endif

        @if ($errors->any())
            <div class="mx-auto mt-4 max-w-7xl rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6">
            @yield('content')
        </main>
    </div>
</body>
</html>
