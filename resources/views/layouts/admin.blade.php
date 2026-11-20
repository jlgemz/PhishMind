<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <title>PhishMind Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased flex h-screen overflow-hidden">
    
    <!-- Admin Sidebar -->
    <aside class="w-64 bg-slate-900 text-white flex flex-col py-6 shrink-0 z-20 shadow-xl">
        <div class="px-6 mb-10 flex items-center gap-3">
            <div class="w-8 h-8 bg-brand-teal rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
            </div>
            <span class="font-bold text-lg tracking-wide">Admin Panel</span>
        </div>
        
        <nav class="flex flex-col gap-1 px-3">
            <a href="{{ route('admin.dashboard') }}" class="px-4 py-3 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.dashboard') ? 'bg-brand-teal text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                Dashboard
            </a>
            <a href="{{ route('admin.scenarios.index') }}" class="px-4 py-3 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.scenarios.*') ? 'bg-brand-teal text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                Manage Scenarios
            </a>
            <a href="{{ route('admin.results') }}" class="px-4 py-3 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.results') ? 'bg-brand-teal text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                Student Results
            </a>
            <a href="{{ route('admin.export') }}" class="px-4 py-3 rounded-lg text-sm font-medium text-slate-400 hover:bg-slate-800 hover:text-white transition flex items-center justify-between">
                Export Data 
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
            </a>
        </nav>

        <div class="mt-auto px-3">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="w-full text-left px-4 py-3 text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition text-sm font-medium">Logout</button>
            </form>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col h-screen overflow-y-auto w-full">
        <header class="h-20 bg-white border-b border-slate-200 flex items-center px-8 shrink-0 shadow-sm">
            <h1 class="text-xl font-bold text-slate-800">@yield('header')</h1>
        </header>
        <div class="p-8 max-w-7xl mx-auto w-full">
            @yield('content')
        </div>
    </main>

     <!-- ADD THIS LINE -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>