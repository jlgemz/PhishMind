<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PhishMind - Security Scanner</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('logo.svg') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: #0a0a0a;
            color: #00ff41;
            font-family: 'Courier New', monospace;
            min-height: 100vh;
        }
        .navbar {
            background: rgba(0,0,0,0.95) !important;
            border-bottom: 2px solid rgba(0,255,65,0.2);
        }
        .navbar-brand {
            color: #00ff41 !important;
            text-shadow: 0 0 20px rgba(0,255,65,0.5);
        }
        .card {
            background: rgba(13,13,13,0.95);
            border: 1px solid rgba(0,255,65,0.2);
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,255,65,0.1);
        }
        .btn-primary {
            background: #00ff41;
            border: none;
            color: #000;
            font-weight: bold;
        }
        .btn-primary:hover {
            background: #00cc33;
            box-shadow: 0 0 30px rgba(0,255,65,0.3);
        }
        .form-control {
            background: rgba(0,0,0,0.8);
            border: 1px solid rgba(0,255,65,0.3);
            color: #00ff41;
        }
        .form-control:focus {
            background: rgba(0,0,0,0.9);
            border-color: #00ff41;
            box-shadow: 0 0 20px rgba(0,255,65,0.2);
            color: #00ff41;
        }
        .progress {
            background: rgba(0,255,65,0.1);
            border: 1px solid rgba(0,255,65,0.2);
        }
        .progress-bar {
            background: #00ff41;
        }
        .status-icon {
            font-size: 4rem;
        }
        .badge-safe { background: rgba(0,255,65,0.2); color: #00ff41; border: 1px solid #00ff41; }
        .badge-suspicious { background: rgba(255,170,0,0.2); color: #ffaa00; border: 1px solid #ffaa00; }
        .badge-dangerous { background: rgba(255,0,64,0.2); color: #ff0040; border: 1px solid #ff0040; }
        .stat-card {
            background: rgba(13,13,13,0.95);
            border: 1px solid rgba(0,255,65,0.2);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #00ff41;
        }
        ::-webkit-scrollbar { width: 10px; }
        ::-webkit-scrollbar-track { background: #0a0a0a; }
        ::-webkit-scrollbar-thumb { background: #00ff41; border-radius: 5px; }
    </style>
    @yield('styles')
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="{{ route('home') }}">
                <i class="bi bi-shield-check fs-3 me-2"></i>
                <span class="fw-bold fs-4">PhishGuard</span>
            </a>
        </div>
    </nav>

    <main class="py-4">
        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html>