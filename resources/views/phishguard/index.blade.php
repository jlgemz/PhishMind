@extends('layout.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card">
                <div class="card-body p-5">
                    <div class="text-center mb-5">
                        <h1 class="display-4 fw-bold text-success">
                            <i class="bi bi-shield-fill-check"></i> PhishGuard
                        </h1>
                        <p class="lead text-light">Advanced Phishing URL Detection with Threat Intelligence</p>
                        <p class="text-light-emphasis">
                            <i class="bi bi-info-circle"></i> 
                            Powered by Machine Learning + VirusTotal + Google Safe Browsing
                        </p>
                        
                        @if(isset($stats))
                        <div class="row mt-4">
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <div class="stat-number">{{ $stats['total_scans'] }}</div>
                                    <div class="text-light-emphasis">Total Scans</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <div class="stat-number text-success">{{ $stats['safe_urls'] }}</div>
                                    <div class="text-light-emphasis">Safe</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <div class="stat-number text-warning">{{ $stats['suspicious_urls'] }}</div>
                                    <div class="text-light-emphasis">Suspicious</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <div class="stat-number text-danger">{{ $stats['dangerous_urls'] }}</div>
                                    <div class="text-light-emphasis">Blocked</div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- URL Input Form -->
                    <div class="row justify-content-center">
                        <div class="col-lg-10">
                            <form id="urlForm" action="{{ route('interceptor.check') }}" method="POST" class="mb-4">
                                @csrf
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-dark border-0">
                                        <i class="bi bi-link-45deg text-success"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control bg-dark border-0" 
                                           id="urlInput" 
                                           name="url"
                                           placeholder="Paste any URL (e.g., google.com, facebook.com)"
                                           value="{{ old('url') }}"
                                           autofocus>
                                    <button class="btn btn-success" type="submit" id="checkButton">
                                        <i class="bi bi-shield-check"></i> Check & Go
                                    </button>
                                </div>
                                <div id="urlError" class="text-danger mt-2 d-none">
                                    <i class="bi bi-exclamation-circle"></i> Please enter a valid URL
                                </div>
                            </form>

                            <!-- Loading Indicator -->
                            <div id="loadingIndicator" class="text-center d-none">
                                <div class="spinner-border text-success" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="text-light-emphasis mt-2">🔍 Analyzing URL with ML + Threat Intelligence...</p>
                            </div>

                            <!-- Quick Examples -->
                            <div class="mt-3">
                                <small class="text-light-emphasis">Try these examples:</small>
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    <button class="btn btn-outline-secondary btn-sm example-btn" data-url="google.com">
                                        google.com ✅
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm example-btn" data-url="github.com">
                                        github.com ✅
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm example-btn" data-url="secure-login.xyz">
                                        secure-login.xyz ⚠️
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm example-btn" data-url="g00gle.com/login">
                                        g00gle.com/login 🚫
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Scans -->
                    @if(isset($recentScans) && $recentScans->count() > 0)
                    <div class="mt-5">
                        <h5 class="text-light"><i class="bi bi-clock-history"></i> Recent Scans</h5>
                        <div class="table-responsive">
                            <table class="table table-dark table-hover">
                                <thead>
                                    <tr>
                                        <th>URL</th>
                                        <th>Status</th>
                                        <th>Confidence</th>
                                        <th>Threat Intel</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentScans as $scan)
                                    <tr>
                                        <td class="text-light">{{ Str::limit($scan->url, 40) }}</td>
                                        <td>
                                            <span class="badge badge-{{ $scan->status }}">
                                                {{ ucfirst($scan->status) }}
                                            </span>
                                        </td>
                                        <td>{{ round($scan->confidence_score) }}%</td>
                                        <td>
                                            @if(isset($scan->metadata['threat_intelligence']))
                                                @if($scan->metadata['threat_intelligence']['is_threat'] ?? false)
                                                    <span class="text-danger">⚠️ Threat Detected</span>
                                                @else
                                                    <span class="text-success">✅ Clean</span>
                                                @endif
                                            @else
                                                <span class="text-light-emphasis">N/A</span>
                                            @endif
                                        </td>
                                        <td class="text-light-emphasis">{{ $scan->created_at->diffForHumans() }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    <!-- Service Status -->
                    <div class="mt-4">
                        <div class="row">
                            <div class="col-md-3">
                                <small class="text-light-emphasis">
                                    ML Service: 
                                    <span id="mlStatus" class="text-success">● Online</span>
                                </small>
                            </div>
                            <div class="col-md-3">
                                <small class="text-light-emphasis">
                                    VirusTotal: 
                                    <span id="vtStatus" class="text-success">● Connected</span>
                                </small>
                            </div>
                            <div class="col-md-3">
                                <small class="text-light-emphasis">
                                    GSB Status: 
                                    <span id="gsbStatus" class="text-warning">● Not Configured</span>
                                </small>
                            </div>
                            <div class="col-md-3">
                                <small class="text-light-emphasis">
                                    AbuseIPDB: 
                                    <span id="abuseipdbStatus" class="text-success">● Connected</span>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('urlForm');
    const urlInput = document.getElementById('urlInput');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const checkButton = document.getElementById('checkButton');
    const urlError = document.getElementById('urlError');
    const exampleBtns = document.querySelectorAll('.example-btn');

    // Example buttons
    exampleBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            urlInput.value = this.dataset.url;
            setTimeout(() => {
                form.submit();
            }, 300);
        });
    });

    // Form submit
    form.addEventListener('submit', function(e) {
        const url = urlInput.value.trim();
        if (!url) {
            e.preventDefault();
            showError('Please enter a URL');
            return;
        }
        
        if (!isValidUrl(url)) {
            e.preventDefault();
            const corrected = autoCorrectUrl(url);
            if (corrected) {
                urlInput.value = corrected;
                setTimeout(() => {
                    this.submit();
                }, 100);
            } else {
                showError('Please enter a valid URL');
            }
            return;
        }
        
        loadingIndicator.classList.remove('d-none');
        checkButton.disabled = true;
    });

    function isValidUrl(string) {
        try {
            if (string.match(/^https?:\/\/.+/)) {
                new URL(string);
                return true;
            }
            const domainPattern = /^[a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]\.[a-zA-Z]{2,}(\.[a-zA-Z]{2,})?$/;
            if (domainPattern.test(string)) return true;
            const subdomainPattern = /^([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}$/;
            if (subdomainPattern.test(string)) return true;
            return false;
        } catch (_) {
            return false;
        }
    }

    function autoCorrectUrl(string) {
        const trimmed = string.trim();
        if (trimmed.match(/^https?:\/\//)) {
            try { new URL(trimmed); return trimmed; } catch (_) { return null; }
        }
        const withHttps = 'https://' + trimmed;
        try { new URL(withHttps); return withHttps; } catch (_) {}
        const withHttp = 'http://' + trimmed;
        try { new URL(withHttp); return withHttp; } catch (_) {}
        if (preg_match('/^[a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]\.[a-zA-Z]{2,}$/', trimmed)) {
            return 'https://' + trimmed;
        }
        return null;
    }

    function showError(message) {
        urlError.textContent = message;
        urlError.classList.remove('d-none');
        urlInput.classList.add('is-invalid');
    }

    // Check ML Status
    async function checkMLStatus() {
        try {
            const response = await fetch('http://localhost:8001/health');
            if (response.ok) {
                const data = await response.json();
                const statusElement = document.getElementById('mlStatus');
                if (data.model_loaded) {
                    statusElement.innerHTML = '● Online';
                    statusElement.className = 'text-success';
                } else {
                    statusElement.innerHTML = '⚠️ Degraded';
                    statusElement.className = 'text-warning';
                }
            }
        } catch (e) {
            document.getElementById('mlStatus').innerHTML = '● Offline';
            document.getElementById('mlStatus').className = 'text-danger';
        }
    }

    // Check VirusTotal Status
    async function checkVTStatus() {
        try {
            const response = await fetch('/test/virustotal');
            if (response.ok) {
                const data = await response.json();
                const statusElement = document.getElementById('vtStatus');
                if (data.status === 'success') {
                    statusElement.innerHTML = '● Connected';
                    statusElement.className = 'text-success';
                } else {
                    statusElement.innerHTML = '⚠️ Error';
                    statusElement.className = 'text-warning';
                }
            }
        } catch (e) {
            document.getElementById('vtStatus').innerHTML = '● Offline';
            document.getElementById('vtStatus').className = 'text-danger';
        }
    }

    // Initial checks
    setTimeout(checkMLStatus, 1000);
    setTimeout(checkVTStatus, 2000);
    setInterval(checkMLStatus, 30000);


    // Check Google Safe Browsing Status
    async function checkGSBStatus() {
        try {
            const response = await fetch('/test/gsb');
            if (response.ok) {
                const data = await response.json();
                const statusElement = document.getElementById('gsbStatus');
                if (data.status === 'success' || data.status === 'disabled') {
                    if (data.safe !== undefined) {
                        statusElement.innerHTML = '● Connected';
                        statusElement.className = 'text-success';
                    } else {
                        statusElement.innerHTML = '⚠️ Error';
                        statusElement.className = 'text-warning';
                    }
                } else {
                    statusElement.innerHTML = '⚠️ Error';
                    statusElement.className = 'text-warning';
                }
            }
        } catch (e) {
            document.getElementById('gsbStatus').innerHTML = '● Offline';
            document.getElementById('gsbStatus').className = 'text-danger';
        }
    }

    // Call it on page load
    setTimeout(checkGSBStatus, 3000);

    // Check AbuseIPDB Status
    async function checkAbuseIPDBStatus() {
        try {
            const response = await fetch('/test/abuseipdb');
            if (response.ok) {
                const data = await response.json();
                const statusElement = document.getElementById('abuseipdbStatus');
                if (data.status === 'success') {
                    statusElement.innerHTML = '● Connected';
                    statusElement.className = 'text-success';
                } else {
                    statusElement.innerHTML = '⚠️ Error';
                    statusElement.className = 'text-warning';
                }
            }
        } catch (e) {
            document.getElementById('abuseipdbStatus').innerHTML = '● Offline';
            document.getElementById('abuseipdbStatus').className = 'text-danger';
        }
    }

    // Call it on page load
    setTimeout(checkAbuseIPDBStatus, 3000);
});
</script>
@endsection
