<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Daily')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-100 text-slate-800 antialiased">
    <div class="min-h-full">
        <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/90 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center gap-6 px-4 py-3 sm:px-6">
                <a href="{{ route('focus') }}" class="flex items-center gap-2 font-semibold text-slate-900">
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
                           class="rounded-lg px-3 py-1.5 transition {{ $active ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>

                <div class="ml-auto text-sm text-slate-500">{{ now()->format('D j M Y') }}</div>
            </div>
        </header>

        @if (session('status'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3500)" x-cloak
                 class="mx-auto mt-4 flex max-w-7xl items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm text-emerald-800">
                <span>{{ session('status') }}</span>
                <button type="button" @click="show = false" class="ml-auto text-emerald-600 hover:text-emerald-900">&times;</button>
            </div>
        @endif

        @if ($errors->any())
            <div class="mx-auto mt-4 max-w-7xl rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm text-rose-800">
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
