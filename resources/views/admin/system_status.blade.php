@extends('layouts.admin')
@section('header', 'System Monitor')

@section('content')
<div class="max-w-4xl mx-auto" x-data="systemMonitor()">
    <div class="mb-8 flex justify-between items-end">
        <div>
            <h2 class="text-3xl font-bold text-slate-800 mb-2">System Services Monitor</h2>
            <p class="text-slate-600">Check and control the ML and Ollama services.</p>
        </div>
        <button @click="refresh()" class="text-brand-teal font-bold text-sm hover:underline flex items-center gap-1">
            <svg class="w-4 h-4" :class="{'animate-spin': loading}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            Refresh
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- ML Service -->
        <div class="bg-white rounded-2xl border border-slate-200 p-8 shadow-sm">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h3 class="text-lg font-bold text-slate-800">ML Service</h3>
                    <p class="text-sm text-slate-500 font-mono">http://127.0.0.1:8001/</p>
                </div>
                
                <!-- Status Badge -->
                <div>
                    <span x-show="mlStatus" class="bg-emerald-50 text-emerald-700 border border-emerald-200 px-3 py-1 rounded-md text-xs font-bold uppercase">Online</span>
                    <span x-show="!mlStatus" class="bg-rose-50 text-rose-700 border border-rose-200 px-3 py-1 rounded-md text-xs font-bold uppercase">Offline</span>
                </div>
            </div>
            
            <!-- Toggle Switch -->
            <div class="mt-6 flex items-center justify-between bg-slate-50 p-4 rounded-xl border border-slate-200">
                <div>
                    <p class="text-sm font-bold text-slate-700">Power State</p>
                    <p class="text-xs text-slate-500" x-text="mlStatus ? 'Service is currently running.' : 'Service is stopped.'"></p>
                </div>
                <button @click="toggle('ml')" :disabled="toggling === 'ml'" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none" :class="mlStatus ? 'bg-brand-teal' : 'bg-slate-300'">
                    <span class="sr-only">Use setting</span>
                    <span aria-hidden="true" :class="mlStatus ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                </button>
            </div>
        </div>

        <!-- Ollama Service -->
        <div class="bg-white rounded-2xl border border-slate-200 p-8 shadow-sm">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h3 class="text-lg font-bold text-slate-800">Ollama</h3>
                    <p class="text-sm text-slate-500 font-mono">{{ $ollamaStatus['url'] ?? 'http://127.0.0.1:11501/' }}</p>
                </div>
                
                <!-- Status Badge -->
                <div>
                    <span x-show="ollamaStatus" class="bg-emerald-50 text-emerald-700 border border-emerald-200 px-3 py-1 rounded-md text-xs font-bold uppercase">Online</span>
                    <span x-show="!ollamaStatus" class="bg-rose-50 text-rose-700 border border-rose-200 px-3 py-1 rounded-md text-xs font-bold uppercase">Offline</span>
                </div>
            </div>
            
            <!-- Toggle Switch -->
            <div class="mt-6 flex items-center justify-between bg-slate-50 p-4 rounded-xl border border-slate-200">
                <div>
                    <p class="text-sm font-bold text-slate-700">Power State</p>
                    <p class="text-xs text-slate-500" x-text="ollamaStatus ? 'Service is currently running.' : 'Service is stopped.'"></p>
                </div>
                <button @click="toggle('ollama')" :disabled="toggling === 'ollama'" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none" :class="ollamaStatus ? 'bg-brand-teal' : 'bg-slate-300'">
                    <span class="sr-only">Use setting</span>
                    <span aria-hidden="true" :class="ollamaStatus ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Manual Refresh Area -->
    <div class="mt-8 text-center text-sm text-slate-400">
        Status auto-checks every 5 seconds.
    </div>
</div>

<script>
    function systemMonitor() {
        return {
            mlStatus: false,
            ollamaStatus: false,
            loading: false,
            toggling: null,

            init() {
                // Initial fetch based on server-side variables
                this.mlStatus = {{ $mlStatus['online'] ? 'true' : 'false' }};
                this.ollamaStatus = {{ $ollamaStatus['online'] ? 'true' : 'false' }};
                
                // Auto-refresh status every 5 seconds
                setInterval(() => {
                    this.refresh(true); // silent refresh
                }, 5000);
            },

            async refresh(silent = false) {
                if (!silent) this.loading = true;
                try {
                    const res = await fetch('{{ route('admin.system.status') }}', {
                        headers: { 'Accept': 'application/json' }
                    });
                    
                    if (res.ok) {
                        const data = await res.json();
                        this.mlStatus = data.ml_online;
                        this.ollamaStatus = data.ollama_online;
                    }
                } catch (e) {
                    // Ignore errors during silent refresh
                }
                this.loading = false;
            },

            async toggle(service) {
                this.toggling = service;
                const action = (service === 'ml' ? this.mlStatus : this.ollamaStatus) ? 'stop' : 'start';

                try {
                    const res = await fetch('{{ route('admin.system.toggle') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ service: service, action: action })
                    });
                    
                    const data = await res.json();
                    if (data.success) {
                        // Wait 1.5 seconds for the service to boot, then refresh status
                        setTimeout(() => this.refresh(), 1500);
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (err) {
                    alert('Network error. Check server logs.');
                } finally {
                    this.toggling = null;
                }
            }
        }
    }
</script>
@endsection