<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>PhishMind</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('logo.svg') }}">
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

    <!-- Student Sidebar -->
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
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                <img src="{{ asset('logo.svg') }}" alt="PhishMind Logo" class="w-10 h-10 rounded-xl shrink-0 shadow-xs object-contain">
                <div x-show="desktopSidebar" class="flex flex-col md:transition-opacity">
                    <span class="text-white font-bold text-lg leading-tight tracking-tight">PhishMind</span>
                    <span class="text-[11px] text-teal-300 font-medium tracking-wide uppercase">Learning Lab</span>
                </div>
            </a>
            <!-- Mobile Close Button -->
            <button @click="sidebarOpen = false" 
                    class="md:hidden text-slate-400 hover:text-white p-2 rounded-xl focus:outline-none min-w-[44px] min-h-[44px] flex items-center justify-center transition" 
                    aria-label="Close Sidebar">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        
        <!-- Navigation Links -->
        <nav class="flex flex-col gap-1.5 w-full px-3">
            <a href="{{ route('dashboard') }}" 
               class="flex items-center gap-3.5 px-3.5 py-3 rounded-xl transition min-h-[44px] {{ request()->routeIs('dashboard') ? 'bg-brand-teal text-white shadow-xs font-semibold' : 'text-slate-400 hover:text-white hover:bg-white/10' }}"
               :class="{ 'md:justify-center md:px-0': !desktopSidebar }"
               title="Dashboard">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                <span :class="{ 'md:hidden': !desktopSidebar }" class="text-sm font-medium">Dashboard</span>
            </a>

            <a href="{{ route('practice.select') }}" 
               class="flex items-center gap-3.5 px-3.5 py-3 rounded-xl transition min-h-[44px] {{ request()->routeIs('practice.*') || request()->routeIs('learning.*') ? 'bg-brand-teal text-white shadow-xs font-semibold' : 'text-slate-400 hover:text-white hover:bg-white/10' }}"
               :class="{ 'md:justify-center md:px-0': !desktopSidebar }"
               title="Practice Scenarios">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                <span :class="{ 'md:hidden': !desktopSidebar }" class="text-sm font-medium">Practice Lab</span>
            </a>

            <a href="{{ route('check.index') }}" 
               class="flex items-center gap-3.5 px-3.5 py-3 rounded-xl transition min-h-[44px] {{ request()->routeIs('check.*') ? 'bg-brand-teal text-white shadow-xs font-semibold' : 'text-slate-400 hover:text-white hover:bg-white/10' }}"
               :class="{ 'md:justify-center md:px-0': !desktopSidebar }"
               title="Check Content">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                <span :class="{ 'md:hidden': !desktopSidebar }" class="text-sm font-medium">Check Content</span>
            </a>

            <a href="{{ route('profile') }}" 
               class="flex items-center gap-3.5 px-3.5 py-3 rounded-xl transition min-h-[44px] {{ request()->routeIs('profile') ? 'bg-brand-teal text-white shadow-xs font-semibold' : 'text-slate-400 hover:text-white hover:bg-white/10' }}"
               :class="{ 'md:justify-center md:px-0': !desktopSidebar }"
               title="My Profile">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                <span :class="{ 'md:hidden': !desktopSidebar }" class="text-sm font-medium">My Profile</span>
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

                <h1 class="text-base sm:text-xl font-bold text-slate-800 truncate">@yield('header', 'Dashboard')</h1>
            </div>

            <!-- User Info Badge -->
            <div class="flex items-center gap-3 sm:gap-4 shrink-0">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-bold text-slate-900 leading-tight truncate max-w-[150px]">{{ auth()->user()->name ?? 'Maya Santos' }}</p>
                    <p class="text-xs text-brand-teal font-medium">Student</p>
                </div>
                <div class="w-9 h-9 sm:w-10 sm:h-10 bg-teal-50 border border-teal-200 text-brand-teal rounded-xl flex items-center justify-center font-bold text-sm sm:text-base shrink-0 shadow-2xs">
                    {{ substr(auth()->user()->name ?? 'M', 0, 1) }}
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
