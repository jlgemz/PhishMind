<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- ADD THIS LINE: Required for AJAX/Fetch CSRF protection -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>PhishMind</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#f8fafc] text-slate-800 font-sans antialiased flex h-screen overflow-hidden">
    
    <!-- Sidebar -->
    <aside class="w-20 bg-brand-dark flex flex-col items-center py-6 shrink-0 z-20">
        <a href="{{ route('dashboard') }}" class="w-10 h-10 bg-white rounded-lg flex items-center justify-center mb-10">
            <svg class="w-6 h-6 text-brand-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
        </a>
        
        <nav class="flex flex-col gap-6 w-full">
            <a href="{{ route('dashboard') }}" class="flex justify-center p-3 mx-2 transition {{ request()->routeIs('dashboard') ? 'bg-brand-teal text-white rounded-xl' : 'text-slate-400 hover:text-white' }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
            </a>
            <a href="{{ route('practice.select') }}" class="flex justify-center p-3 mx-2 transition {{ request()->routeIs('practice.*') || request()->routeIs('learning.*') ? 'bg-brand-teal text-white rounded-xl' : 'text-slate-400 hover:text-white' }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
            </a>
            <a href="{{ route('check.index') }}" class="flex justify-center p-3 mx-2 transition {{ request()->routeIs('check.*') ? 'bg-brand-teal text-white rounded-xl' : 'text-slate-400 hover:text-white' }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </a>
            <a href="{{ route('profile') }}" class="flex justify-center p-3 mx-2 transition {{ request()->routeIs('profile') ? 'bg-brand-teal text-white rounded-xl' : 'text-slate-400 hover:text-white' }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
            </a>
        </nav>

        <div class="mt-auto">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="text-slate-400 hover:text-white p-3 transition"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg></button>
            </form>
        </div>
    </aside>

    <main class="flex-1 flex flex-col h-screen overflow-y-auto">
        <header class="h-20 bg-white border-b border-slate-200 flex items-center justify-between px-8 shrink-0">
            <h1 class="text-xl font-semibold text-slate-800">@yield('header', 'Dashboard')</h1>
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <p class="text-sm font-bold text-slate-900">{{ auth()->user()->name ?? 'Maya Santos' }}</p>
                    <p class="text-xs text-slate-500">University Student</p>
                </div>
                <div class="w-10 h-10 bg-teal-100 text-brand-teal rounded-full flex items-center justify-center font-bold">
                    {{ substr(auth()->user()->name ?? 'M', 0, 1) }}
                </div>
            </div>
        </header>

        <div class="p-8 max-w-6xl mx-auto w-full">
            @yield('content')
        </div>
    </main>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>