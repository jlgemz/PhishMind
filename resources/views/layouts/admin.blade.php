<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <title>PhishMind Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body x-data="{ 
        sidebarOpen: false, 
        desktopSidebar: true,
        toggleSidebar() {
            if (window.innerWidth < 768) {
                this.sidebarOpen = !this.sidebarOpen;
            } else {
                this.desktopSidebar = !this.desktopSidebar;
            }
        }
    }" 
    @keydown.escape.window="sidebarOpen = false" 
    class="bg-[#f8fafc] text-slate-800 font-sans antialiased flex h-screen overflow-hidden">
    
    <!-- Mobile Backdrop Overlay -->
    <div x-show="sidebarOpen" 
         @click="sidebarOpen = false" 
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-40 md:hidden" 
         style="display: none;"></div>

    <!-- Admin Sidebar -->
    <aside :class="{
               'translate-x-0 shadow-2xl': sidebarOpen,
               '-translate-x-full': !sidebarOpen,
               'md:translate-x-0': true,
               'md:w-64': desktopSidebar,
               'md:w-20': !desktopSidebar
           }"
           class="fixed inset-y-0 left-0 z-50 w-64 bg-brand-dark flex flex-col py-5 shrink-0 transition-all duration-300 ease-in-out md:static md:shadow-none">
        
        <!-- Sidebar Brand & Header -->
        <div class="flex items-center justify-between px-5 mb-8 w-full"
             :class="{ 'md:justify-center md:px-0': !desktopSidebar }">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3">
                <div class="w-10 h-10 bg-brand-teal text-white rounded-xl flex items-center justify-center shrink-0 shadow-xs">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                </div>
                <div x-show="desktopSidebar" class="flex flex-col">
                    <span class="text-white font-bold text-lg leading-tight tracking-tight">PhishMind</span>
                    <span class="text-[11px] text-teal-300 font-medium tracking-wide uppercase">Admin Portal</span>
                </div>
            </a>
            <!-- Mobile Close Button -->
            <button @click="sidebarOpen = false" 
                    class="md:hidden text-slate-400 hover:text-white p-2 rounded-xl focus:outline-none min-w-[44px] min-h-[44px] flex items-center justify-center transition" 
                    aria-label="Close Admin Menu">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        
        <!-- Navigation Links -->
        <nav class="flex flex-col gap-1.5 w-full px-3">
            <a href="{{ route('admin.dashboard') }}" 
               class="flex items-center gap-3.5 px-3.5 py-3 rounded-xl transition min-h-[44px] {{ request()->routeIs('admin.dashboard') ? 'bg-brand-teal text-white shadow-xs font-semibold' : 'text-slate-400 hover:text-white hover:bg-white/10' }}"
               :class="{ 'md:justify-center md:px-0': !desktopSidebar }"
               title="Dashboard">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                <span :class="{ 'md:hidden': !desktopSidebar }" class="text-sm font-medium">Dashboard</span>
            </a>

            <a href="{{ route('admin.scenarios.index') }}" 
               class="flex items-center gap-3.5 px-3.5 py-3 rounded-xl transition min-h-[44px] {{ request()->routeIs('admin.scenarios.*') ? 'bg-brand-teal text-white shadow-xs font-semibold' : 'text-slate-400 hover:text-white hover:bg-white/10' }}"
               :class="{ 'md:justify-center md:px-0': !desktopSidebar }"
               title="Manage Scenarios">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                <span :class="{ 'md:hidden': !desktopSidebar }" class="text-sm font-medium">Manage Scenarios</span>
            </a>

            <a href="{{ route('admin.results') }}" 
               class="flex items-center gap-3.5 px-3.5 py-3 rounded-xl transition min-h-[44px] {{ request()->routeIs('admin.results') ? 'bg-brand-teal text-white shadow-xs font-semibold' : 'text-slate-400 hover:text-white hover:bg-white/10' }}"
               :class="{ 'md:justify-center md:px-0': !desktopSidebar }"
               title="Student Results">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                <span :class="{ 'md:hidden': !desktopSidebar }" class="text-sm font-medium">Student Results</span>
            </a>

            <a href="{{ route('admin.export') }}" 
               class="flex items-center gap-3.5 px-3.5 py-3 rounded-xl transition min-h-[44px] text-slate-400 hover:text-white hover:bg-white/10"
               :class="{ 'md:justify-center md:px-0': !desktopSidebar }"
               title="Export Data">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                <span :class="{ 'md:hidden': !desktopSidebar }" class="text-sm font-medium">Export Data</span>
            </a>
        </nav>

        <!-- Logout Section -->
        <div class="mt-auto px-3 w-full">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" 
                        class="w-full flex items-center gap-3.5 text-slate-400 hover:text-rose-300 hover:bg-rose-500/10 px-3.5 py-3 transition rounded-xl min-h-[44px]"
                        :class="{ 'md:justify-center md:px-0': !desktopSidebar }"
                        title="Log Out">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    <span :class="{ 'md:hidden': !desktopSidebar }" class="text-sm font-medium">Log Out</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col h-screen overflow-y-auto min-w-0">
        <!-- Top Navigation Bar -->
        <header class="h-16 sm:h-20 bg-white border-b border-slate-200 flex items-center justify-between px-4 sm:px-6 md:px-8 shrink-0 sticky top-0 z-30">
            <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                <!-- Hamburger Button (Present on All Screen Sizes) -->
                <button @click="toggleSidebar()" 
                        type="button" 
                        class="p-2 -ml-2 rounded-xl text-slate-600 hover:text-brand-teal hover:bg-teal-50 focus:outline-none focus:ring-2 focus:ring-brand-teal min-w-[44px] min-h-[44px] flex items-center justify-center transition shrink-0" 
                        title="Toggle Navigation Menu"
                        aria-label="Toggle Navigation Menu">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <h1 class="text-base sm:text-xl font-bold text-slate-800 truncate">@yield('header')</h1>
            </div>

            <!-- Admin Badge -->
            <div class="flex items-center gap-3 sm:gap-4 shrink-0">
                <span class="bg-teal-50 border border-teal-200 text-brand-teal px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">
                    Admin
                </span>
                <div class="w-9 h-9 sm:w-10 sm:h-10 bg-brand-dark text-white rounded-xl flex items-center justify-center font-bold text-sm sm:text-base shrink-0 shadow-2xs">
                    A
                </div>
            </div>
        </header>

        <!-- Content Container with Responsive Padding -->
        <div class="p-4 sm:p-6 md:p-8 max-w-7xl mx-auto w-full">
            @yield('content')
        </div>
    </main>

</body>
</html>
