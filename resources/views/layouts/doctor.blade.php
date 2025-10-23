{{-- resources/views/layouts/doctor.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Doctor Panel • PatientCare</title>

  {{-- Bootstrap 5 --}}
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  {{-- Font Awesome --}}
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  {{-- SweetAlert2 --}}
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


  <style>
    html, body {
      height: 100%;
      margin: 0;
    }
    .sidebar {
      position: fixed;
      top: 0; bottom: 0; left: 0;
      width: 240px;
      display: flex;
      flex-direction: column;
      background-color: #00529A;
      overflow-y: auto;
      padding: 1rem;
    }
    main {
      margin-left: 240px;
      height: 100vh;
      overflow-y: auto;
      padding: 1.5rem;
      padding-top: 60px; /* Added padding to prevent overlap */
    }
    .logo { width: 80px; }
    .avatar {
      width: 90px; height: 90px;
      background-color: aliceblue;
      border-radius: 50%;
      margin: 0 auto 1rem;
    }
    .nav-link {
      transition: background-color 0.2s ease;
      border-radius: 0.375rem;
      color: #fff;
    }
    .nav-link:hover {
      background-color: rgba(255,255,255,0.2);
      color: #fff !important;
    }
    .nav-link.active {
      background-color: #fff;
      color: #00529A !important;
    }
    .footer {
      margin-top: auto;
    }
    .section-label {
      text-transform: uppercase;
      font-size: 0.75rem;
      color: rgba(255,255,255,0.5);
      margin: 1rem 0 0.5rem;
    }
  </style>
</head>
<body>

  {{-- Sidebar --}}
  <aside class="sidebar text-white">
    <div class="text-center mb-4">
      <img src="{{ asset('images/patientcare-logo-white.png') }}" class="logo img-fluid mb-3" alt="Logo">
      <div class="avatar"></div>
      <strong>{{ Auth::user()->username ?? 'Physician User' }}</strong><br>
      <small>Physician ID: {{ Auth::id() }}</small>
    </div>

    {{-- Navigation --}}
    <nav class="nav flex-column mb-4">
      <a href="{{ route('doctor.dashboard') }}"
         class="nav-link d-flex align-items-center mb-2 {{ request()->routeIs('doctor.dashboard') ? 'active' : '' }}">
        <i class="fas fa-home fa-lg me-2"></i>
        <span>Dashboard</span>
      </a>

      <a href="{{ route('doctor.orders.index') }}"
         class="nav-link d-flex align-items-center mb-2 {{ request()->routeIs('doctor.orders.*') ? 'active' : '' }}">
        <i class="fas fa-clipboard-list fa-lg me-2"></i>
        <span>Patient Orders</span>
      </a>

      <a href="{{ route('doctor.nurse-requests') }}"
         class="nav-link d-flex align-items-center mb-2 {{ request()->routeIs('doctor.nurse-requests.*') ? 'active' : '' }}">
        <i class="fas fa-user-nurse fa-lg me-2"></i>
        <span>Nurse Requests</span>
      </a>

      {{-- Add more doctor-specific links here --}}
    </nav>

    {{-- Footer --}}
    <div class="footer text-center">
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit"
                class="btn btn-sm btn-outline-light w-100 text-start mb-3">
          <i class="fas fa-sign-out-alt me-2"></i> Logout
        </button>
      </form>
      <small>PatientCare © {{ date('Y') }}</small><br>
      <sup>V1.0.0</sup>
    </div>
  </aside>

  {{-- Main Content --}}
  <main>
    <div id="connection-status" class="d-inline-flex align-items-center rounded-pill px-3 py-1 mb-3 shadow-sm" style="position:fixed;top:18px;right:30px;z-index:1050;display:none;font-size:0.95rem;">
      <span id="connection-dot" style="width:10px;height:10px;border-radius:50%;display:inline-block;margin-right:8px;background:#17c671;"></span>
      <span id="connection-message"></span>
      <span id="ping-message" class="ms-2 text-muted" style="font-size:0.85em;"></span>
    </div>
    @yield('content')
  </main>

  {{-- Bootstrap JS --}}
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  @stack('scripts')

  <script>
function updateConnectionStatus() {
  const statusDiv = document.getElementById('connection-status');
  const msgSpan = document.getElementById('connection-message');
  const dot = document.getElementById('connection-dot');
  const pingSpan = document.getElementById('ping-message');
  if (!statusDiv || !msgSpan || !dot || !pingSpan) return;

  if (navigator.onLine) {
    statusDiv.className = 'd-inline-flex align-items-center rounded-pill px-3 py-1 mb-3 shadow-sm bg-success-subtle border border-success';
    dot.style.background = '#17c671';
    msgSpan.textContent = 'Online';
    statusDiv.style.display = '';
    pingSpan.textContent = '...';
    pingCheck();
  } else {
    statusDiv.className = 'd-inline-flex align-items-center rounded-pill px-3 py-1 mb-3 shadow-sm bg-warning-subtle border border-warning';
    dot.style.background = '#ffc107';
    msgSpan.textContent = 'Offline';
    pingSpan.textContent = '';
    statusDiv.style.display = '';
  }
}

// Ping google.com to check latency
function pingCheck() {
  const pingSpan = document.getElementById('ping-message');
  const start = performance.now();
  fetch("https://www.google.com/favicon.ico", {mode:'no-cors',cache:'no-store'})
    .then(() => {
      const ms = Math.round(performance.now() - start);
      pingSpan.textContent = `Ping: ${ms} ms`;
    })
    .catch(() => {
      pingSpan.textContent = 'Ping: timeout';
    });
}

// Initial check
document.addEventListener('DOMContentLoaded', updateConnectionStatus);
window.addEventListener('online', updateConnectionStatus);
window.addEventListener('offline', updateConnectionStatus);

// Optionally, update ping every 30s if online
setInterval(() => {
  if (navigator.onLine) pingCheck();
}, 30000);
  </script>
</body>
</html>
