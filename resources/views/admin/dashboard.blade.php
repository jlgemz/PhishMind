@extends('layouts.admin')

@section('header', 'Researcher Dashboard')

@section('content')
@if(session('success'))
    <div class="bg-emerald-50 text-emerald-700 p-4 rounded-xl mb-6 text-sm font-bold border border-emerald-200">
        ✅ {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="bg-rose-50 text-rose-700 p-4 rounded-xl mb-6 text-sm font-bold border border-rose-200">
        ❌ {{ session('error') }}
    </div>
@endif

<!-- SYSTEM MONITOR SECTION -->
<div class="mb-8" x-data="systemMonitor()">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-3 mb-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 mb-1">System Services</h2>
            <p class="text-slate-500 text-sm">Toggle the ML and Ollama services on or off.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- ML Service Card -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h3 class="text-lg font-bold text-slate-800">ML Service</h3>
                    <p class="text-sm text-slate-500 font-mono">http://127.0.0.1:8001/</p>
                </div>
                <div>
                    <span x-show="mlStatus" class="bg-emerald-50 text-emerald-700 border border-emerald-200 px-3 py-1 rounded-md text-xs font-bold uppercase">Online</span>
                    <span x-show="!mlStatus" class="bg-rose-50 text-rose-700 border border-rose-200 px-3 py-1 rounded-md text-xs font-bold uppercase">Offline</span>
                </div>
            </div>
            
            <div class="mt-4 flex items-center justify-between bg-slate-50 p-4 rounded-xl border border-slate-200">
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

        <!-- Ollama Service Card -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h3 class="text-lg font-bold text-slate-800">Ollama</h3>
                    <p class="text-sm text-slate-500 font-mono" x-text="ollamaUrl">{{ $ollamaStatus['url'] ?? 'http://127.0.0.1:11434/' }}</p>
                </div>
                <div>
                    <span x-show="ollamaStatus" class="bg-emerald-50 text-emerald-700 border border-emerald-200 px-3 py-1 rounded-md text-xs font-bold uppercase">Online</span>
                    <span x-show="!ollamaStatus" class="bg-rose-50 text-rose-700 border border-rose-200 px-3 py-1 rounded-md text-xs font-bold uppercase">Offline</span>
                </div>
            </div>
            
            <div class="mt-4 flex items-center justify-between bg-slate-50 p-4 rounded-xl border border-slate-200">
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
</div>

<!-- ORIGINAL OVERVIEW STATS -->
<div class="mb-8">
    <h2 class="text-2xl font-bold text-slate-800 mb-1">System Overview</h2>
    <p class="text-slate-500 text-sm">Monitor student activity and dataset statistics.</p>
</div>

<!-- Stat Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex items-center gap-4">
        <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
        </div>
        <div><p class="text-xs text-slate-500 font-bold uppercase mb-1">Total Students</p><p class="text-2xl font-bold text-slate-800">{{ $stats['total_students'] }}</p></div>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex items-center gap-4">
        <div class="w-12 h-12 bg-teal-50 text-brand-teal rounded-xl flex items-center justify-center shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
        </div>
        <div><p class="text-xs text-slate-500 font-bold uppercase mb-1">Total Scenarios</p><p class="text-2xl font-bold text-brand-teal">{{ number_format($stats['total_scenarios']) }}</p></div>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex items-center gap-4">
        <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
        </div>
        <div><p class="text-xs text-slate-500 font-bold uppercase mb-1">Total Attempts</p><p class="text-2xl font-bold text-slate-800">{{ number_format($stats['total_attempts']) }}</p></div>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex items-center gap-4">
        <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <div><p class="text-xs text-slate-500 font-bold uppercase mb-1">Correct attempts</p><p class="text-2xl font-bold text-emerald-600">{{ number_format($stats['global_mastery']) }}</p></div>
    </div>
</div>

<!-- Dataset Management -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8" x-data="trainingManager()">

    <!-- URL Dataset Card -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <h3 class="text-lg font-bold text-slate-800 mb-2">URL Dataset</h3>
        <p class="text-sm text-slate-500 mb-4">Upload a new CSV or retrain the existing URL model.</p>

        <form action="{{ route('admin.datasets.upload') }}" method="POST" enctype="multipart/form-data" class="mb-4 border-b border-slate-100 pb-4">
            @csrf
            <input type="hidden" name="dataset_type" value="url">
            <input type="file" name="file" accept=".csv" required class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-slate-800 file:text-white hover:file:bg-slate-900 mb-3">
            <button type="submit" class="w-full bg-slate-800 text-white py-2 rounded-lg font-semibold hover:bg-slate-900 transition text-sm">Step 1: Upload URL Dataset</button>
        </form>

        <button @click="train('url')" :disabled="loading" class="w-full bg-brand-teal text-white py-2.5 rounded-lg font-semibold hover:bg-teal-700 transition text-sm flex justify-center items-center gap-2">
            <svg x-show="loading && type === 'url'" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            <span x-text="loading && type === 'url' ? 'Training... (Do not close)' : 'Step 2: Train URL Model'"></span>
        </button>
    </div>

    <!-- Email Dataset Card -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <h3 class="text-lg font-bold text-slate-800 mb-2">Email Dataset</h3>
        <p class="text-sm text-slate-500 mb-4">Upload a new CSV or retrain the existing Email model.</p>

        <form action="{{ route('admin.datasets.upload') }}" method="POST" enctype="multipart/form-data" class="mb-4 border-b border-slate-100 pb-4">
            @csrf
            <input type="hidden" name="dataset_type" value="email">
            <input type="file" name="file" accept=".csv" required class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-slate-800 file:text-white hover:file:bg-slate-900 mb-3">
            <button type="submit" class="w-full bg-slate-800 text-white py-2 rounded-lg font-semibold hover:bg-slate-900 transition text-sm">Step 1: Upload Email Dataset</button>
        </form>

        <button @click="train('email')" :disabled="loading" class="w-full bg-brand-teal text-white py-2.5 rounded-lg font-semibold hover:bg-teal-700 transition text-sm flex justify-center items-center gap-2">
            <svg x-show="loading && type === 'email'" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            <span x-text="loading && type === 'email' ? 'Training... (Do not close)' : 'Step 2: Train Email Model'"></span>
        </button>
    </div>

    <!-- Messages Dataset Card -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm md:col-span-2 lg:col-span-1">
        <h3 class="text-lg font-bold text-slate-800 mb-2">Messages Dataset</h3>
        <p class="text-sm text-slate-500 mb-4">Upload a Messages CSV (v1/v2 or label/text) or retrain the Messages model.</p>

        <form action="{{ route('admin.datasets.upload') }}" method="POST" enctype="multipart/form-data" class="mb-4 border-b border-slate-100 pb-4">
            @csrf
            <input type="hidden" name="dataset_type" value="sms">
            <input type="file" name="file" accept=".csv" required class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-slate-800 file:text-white hover:file:bg-slate-900 mb-3">
            <button type="submit" class="w-full bg-slate-800 text-white py-2 rounded-lg font-semibold hover:bg-slate-900 transition text-sm">Step 1: Upload Messages Dataset</button>
        </form>

        <button @click="train('sms')" :disabled="loading" class="w-full bg-brand-teal text-white py-2.5 rounded-lg font-semibold hover:bg-teal-700 transition text-sm flex justify-center items-center gap-2">
            <svg x-show="loading && type === 'sms'" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            <span x-text="loading && type === 'sms' ? 'Training... (Do not close)' : 'Step 2: Train Messages Model'"></span>
        </button>
    </div>
</div>

<!-- System Monitor Script -->
<script>
    function systemMonitor() {
        return {
            mlStatus: {{ $mlStatus['online'] ? 'true' : 'false' }},
            ollamaStatus: {{ $ollamaStatus['online'] ? 'true' : 'false' }},
            ollamaUrl: '{{ $ollamaStatus['url'] ?? 'http://127.0.0.1:11434/' }}',
            toggling: null,

            init() {
                // Auto-refresh status every 5 seconds
                setInterval(() => {
                    this.refresh(true); 
                }, 5000);
            },

            async refresh(silent = false) {
                try {
                    // Ask LARAVEL to check the status (avoids browser CORS issues)
                    const res = await fetch('{{ route('admin.system.status') }}', {
                        headers: { 'Accept': 'application/json' }
                    });
                    
                    if(res.ok) {
                        const data = await res.json();
                        this.mlStatus = data.ml_online;
                        this.ollamaStatus = data.ollama_online;
                        if (data.ollama_url) {
                            this.ollamaUrl = data.ollama_url;
                        }
                    }
                } catch (e) {
                    // Ignore network errors during silent refresh
                }
            },

            async toggle(service) {
                this.toggling = service;
                const currentStatus = (service === 'ml' ? this.mlStatus : this.ollamaStatus);
                const action = currentStatus ? 'stop' : 'start';

                if(!confirm(`Are you sure you want to ${action} the ${service} service?`)) {
                    this.toggling = null;
                    return;
                }

                // Optimistically update status so toggle responds instantly
                if (service === 'ollama') {
                    this.ollamaStatus = (action === 'start');
                } else {
                    this.mlStatus = (action === 'start');
                }

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
                        setTimeout(() => this.refresh(), 1000);
                    } else {
                        if (service === 'ollama') this.ollamaStatus = !this.ollamaStatus;
                        else this.mlStatus = !this.mlStatus;
                        alert('Error: ' + data.message);
                    }
                } catch (err) {
                    if (service === 'ollama') this.ollamaStatus = !this.ollamaStatus;
                    else this.mlStatus = !this.mlStatus;
                    alert('Network error. Check server logs.');
                    console.error(err);
                } finally {
                    this.toggling = null;
                }
            }
        }
    }
</script>

<!-- Training Manager Script -->
<script>
    function trainingManager() {
        return {
            loading: false,
            type: '',
            async train(modelType) {
                if (!confirm('Are you sure you want to retrain this model? This will take a few minutes.')) return;

                this.loading = true;
                this.type = modelType;

                try {
                    const res = await fetch('{{ route("admin.train") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ model_type: modelType })
                    });

                    const data = await res.json();
                    if (data.success) {
                        alert('✅ Success: ' + data.message);
                    } else {
                        alert('❌ Error: ' + data.message);
                    }
                } catch (err) {
                    alert('Network error. Check the Python server terminal.');
                } finally {
                    this.loading = false;
                    this.type = '';
                }
            }
        }
    }
</script>
@endsection