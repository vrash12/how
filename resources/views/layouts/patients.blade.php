<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Patient Panel • PatientCare</title>

  <link href="{{ asset('plugins/datatables.min.css') }}" rel="stylesheet">
  <script src="{{ asset('plugins/datatables.min.js') }}"></script>
  <script>
    // SweetAlert2 script (unchanged)
    /*!
    * sweetalert2 v11.23.0
    * Released under the MIT License.
    */
    // ... existing SweetAlert code ...
  </script>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

  <style>
    html, body {
      height: 100%;
      margin: 0;
    }
    
    /* Sidebar styles */
    .sidebar {
      position: fixed;
      top: 0; bottom: 0; left: 0;
      width: 240px;
      display: flex;
      flex-direction: column;
      background-color: #00529A;
      padding: 1rem;
      overflow-y: auto;
      transition: transform 0.3s ease;
      z-index: 1030;
    }
    
    main {
      margin-left: 240px;
      height: 100vh;
      overflow-y: auto;
      padding: 1.5rem;
      transition: margin-left 0.3s ease;
    }
    
    /* Mobile navbar */
    .mobile-nav {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      background-color: #00529A;
      color: #fff;
      padding: 0.75rem 1rem;
      z-index: 1020;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    /* Mobile hamburger button */
    .menu-toggle {
      background: none;
      border: none;
      color: white;
      font-size: 1.5rem;
      cursor: pointer;
    }
    
    /* Overlay for mobile sidebar */
    .sidebar-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0,0,0,0.5);
      z-index: 1025;
    }
    
    /* Mobile responsive styles */
    @media (max-width: 991.98px) {
      .sidebar {
        transform: translateX(-100%);
        width: 280px;
      }
      
      .sidebar.show {
        transform: translateX(0);
      }
      
      main {
        margin-left: 0;
        padding-top: 4rem;
      }
      
      .mobile-nav {
        display: flex;
        align-items: center;
        justify-content: space-between;
      }
      
      .sidebar-overlay.show {
        display: block;
      }
      
      /* Adjust table scrolling on mobile */
      .table-responsive {
        overflow-x: auto;
      }
    }
    
    /* Other existing styles */
    .logo { width: 80px; }
    .avatar {
      width: 90px; height: 90px;
      border-radius: 50%;
      object-fit: cover;
      background-color: aliceblue;
      border: 3px solid #fff;
      margin: 0 auto 1rem;
    }
    .nav-link {
      transition: background-color .2s;
      border-radius: .375rem;
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
    .icon { width: 30px; text-align: center; }
    .footer {
      margin-top: auto;
    }
    .section-label {
      text-transform: uppercase;
      font-size: 0.75rem;
      color: rgba(255,255,255,0.5);
      margin: 1rem 0 0.5rem;
    }
    
    /* Mobile close button in sidebar */
    .close-sidebar {
      display: none;
      position: absolute;
      right: 15px;
      top: 15px;
      background: none;
      border: none;
      color: white;
      font-size: 1.25rem;
    }
    
    @media (max-width: 991.98px) {
      .close-sidebar {
        display: block;
      }
    }
  </style>

  <style>
  .notification {
    position: fixed;
    left: 50%;
    transform: translateX(-50%);
    min-width: 300px;
    max-width: 360px;
    background: #00529A;
    color: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    padding: 15px 20px;
    font-family: Arial, sans-serif;
    font-size: 14px;
    opacity: 0;
    z-index: 1050;
    margin-bottom: 10px;
  }
  
  /* Make notifications responsive on mobile */
  @media (max-width: 576px) {
    .notification {
      width: 90%;
      min-width: auto;
      max-width: 90%;
    }
  }

  .notification-title {
    font-weight: bold;
    font-size: 16px;
    margin-bottom: 5px;
  }

  @keyframes fadeInOut {
    0% { opacity: 0; transform: translate(-50%, 20px); }
    10% { opacity: 1; transform: translate(-50%, 0); }
    80% { opacity: 1; transform: translate(-50%, 0); }
    100% { opacity: 0; transform: translate(-50%, 20px); }
  }
</style>
</head>
<body>
@php
  $user    = Auth::user();
  $patient = $user->patient;

  $patientIDD = $patient->patient_id;
  $unread  = $user->unreadNotifications->count();
@endphp

{{-- Mobile Top Navigation --}}
<div class="mobile-nav">
  <button class="menu-toggle" id="sidebarToggle">
    <i class="fas fa-bars"></i>
  </button>
  <div class="d-flex align-items-center">
    <img src="{{ asset('images/patientcare-logo-white.png') }}" alt="PatientCare Logo" style="height: 30px;">
  </div>
  <a href="{{ route('patient.notification') }}" class="text-white position-relative">
    <i class="fa-solid fa-bell"></i>
    @if($unread)
      <span class="badge bg-danger position-absolute top-0 start-100 translate-middle">{{ $unread }}</span>
    @endif
  </a>
</div>

{{-- Sidebar Overlay --}}
<div class="sidebar-overlay" id="sidebarOverlay"></div>

{{-- Sidebar --}}
<aside class="sidebar" id="sidebar">
  <button class="close-sidebar" id="closeSidebar">
    <i class="fas fa-times"></i>
  </button>

  {{-- Logo --}}
  <div class="text-center mb-4">
    <img src="{{ asset('images/patientcare-logo-white.png') }}"
         alt="PatientCare Logo"
         class="logo img-fluid">
  </div>

  {{-- User Info --}}
  <div class="text-center mb-4">
    @if($patient && $patient->profile_photo)
      <img src="{{ asset('storage/patient/images/'.$patient->profile_photo) }}"
           class="avatar d-block mx-auto" alt="Avatar">
    @else
      <div class="avatar"></div>
    @endif
    @if($patient)
      <strong class="text-white">{{ $patient->patient_first_name }} {{ $patient->patient_last_name }}</strong><br>
      <small class="text-white">ID: PID-{{ str_pad($patient->patient_id,5,'0',STR_PAD_LEFT) }}</small>
    @endif
  </div>

  {{-- Navigation --}}
  <nav class="nav flex-column mb-4">
    <a href="{{ route('patient.dashboard') }}"
       class="nav-link d-flex align-items-center mb-2 {{ request()->routeIs('patient.dashboard') ? 'active' : '' }}">
      <i class="fas fa-home fa-lg icon"></i>
      <span class="ms-2">Dashboard</span>
    </a>

    <a href="{{ route('patient.account') }}"
       class="nav-link d-flex align-items-center mb-2 {{ request()->routeIs('patient.account') ? 'active' : '' }}">
      <i class="fas fa-user-circle fa-lg icon"></i>
      <span class="ms-2">My Account</span>
    </a>

    <a href="{{ route('patient.billing') }}"
       class="nav-link d-flex align-items-center mb-2 {{ request()->routeIs('patient.billing*') ? 'active' : '' }}">
      <i class="fa-solid fa-file-invoice-dollar fa-lg icon"></i>
      <span class="ms-2">Billing</span>
    </a>

    <a href="{{ route('patient.notification') }}"
       class="nav-link d-flex align-items-center position-relative mb-2 {{ request()->routeIs('patient.notification') ? 'active' : '' }}">
      <i class="fa-solid fa-bell fa-lg icon"></i>
      <span class="ms-2">Notifications</span>
      @if($unread)
        <span class="badge bg-danger position-absolute top-0 end-0 translate-middle">{{ $unread }}</span>
      @endif
    </a>
  </nav>

  @php
    use App\Models\Patient;
    $nones = "";
    if(Patient::where('sub_key', $patient->sub_key)->count() == 1){
      $nones = "none";
    }
  @endphp

  {{-- Footer --}}
  <div class="footer text-center">
    <form style="display: {{$nones}}" id="logForm" method="POST" action="{{ route('login.attempt') }}">
      @csrf
      <input type="hidden" name="mode" value="patientSwitch">
      <input type="hidden" name="sub_key" value="{{$patient->sub_key}}">
      <button type="submit" class="btn btn-sm btn-outline-light w-100 text-start mb-3">
        <i class="fa-solid fa-rotate me-2"></i> Switch account
      </button>
    </form>

    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button type="submit" class="btn btn-sm btn-outline-light w-100 text-start mb-3">
        <i class="fas fa-sign-out-alt me-2"></i> Logout
      </button>
    </form>
    <small class="d-block text-white-50">PatientCare © {{ date('Y') }}</small>
    <sup class="text-white-50">V1.0.0</sup>
  </div>
</aside>

{{-- Main Content --}}
<main>
  @yield('content')
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

{{-- Mobile sidebar functionality --}}
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const closeSidebar = document.getElementById('closeSidebar');
    
    // Toggle sidebar
    sidebarToggle.addEventListener('click', function() {
      sidebar.classList.toggle('show');
      sidebarOverlay.classList.toggle('show');
      document.body.classList.toggle('sidebar-open');
    });
    
    // Close sidebar when clicking overlay
    sidebarOverlay.addEventListener('click', function() {
      sidebar.classList.remove('show');
      sidebarOverlay.classList.remove('show');
      document.body.classList.remove('sidebar-open');
    });
    
    // Close sidebar with X button
    closeSidebar.addEventListener('click', function() {
      sidebar.classList.remove('show');
      sidebarOverlay.classList.remove('show');
      document.body.classList.remove('sidebar-open');
    });
    
    // Close sidebar when clicking on a nav link (on mobile)
    const navLinks = document.querySelectorAll('.sidebar .nav-link');
    navLinks.forEach(link => {
      link.addEventListener('click', function() {
        if (window.innerWidth < 992) {
          sidebar.classList.remove('show');
          sidebarOverlay.classList.remove('show');
          document.body.classList.remove('sidebar-open');
        }
      });
    });
    
    // Handle resize events
    window.addEventListener('resize', function() {
      if (window.innerWidth >= 992) {
        sidebar.classList.remove('show');
        sidebarOverlay.classList.remove('show');
        document.body.classList.remove('sidebar-open');
      }
    });
  });
</script>

{{-- Notification scripts --}}
<script>
  function showNotification(title, message, duration = 2000) {
    const notification = document.createElement('div');
    notification.className = 'notification';

    const notificationTitle = document.createElement('div');
    notificationTitle.className = 'notification-title';
    notificationTitle.innerHTML = title;
    notification.appendChild(notificationTitle);

    const notificationMessage = document.createElement('div');
    notificationMessage.innerHTML = message;
    notification.appendChild(notificationMessage);

    const existing = document.querySelectorAll('.notification');
    notification.style.bottom = `${60 + existing.length * 110}px`;

    document.body.appendChild(notification);
    notification.style.animation = `fadeInOut ${duration / 1000}s forwards`;

    setTimeout(() => {
      notification.remove();
      const remaining = document.querySelectorAll('.notification');
      remaining.forEach((notif, index) => {
        notif.style.bottom = `${60 + index * 70}px`;
      });
    }, duration);
  }
</script>

<script>
function fetchNotifications() {
    fetch('/notifications/latest')
        .then(response => response.json())
        .then(notifications => {
            notifications.forEach(notification => {
                // Show the notification
                showNotification('New Notification', notification.message);

                // Mark as popped
                fetch('/notifications/mark-popped', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ id: notification.id })
                });
            });
        })
        .catch(err => console.error(err));
}

// Poll every 1 second (or adjust as needed)
setInterval(fetchNotifications, 1000);
</script>

@stack('scripts')
</body>
</html>
