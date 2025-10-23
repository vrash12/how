<!DOCTYPE html>
<html lang="en">
  <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Nurse • PatientCare</title>

      {{-- Bootstrap 5 --}}
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
      {{-- Font Awesome --}}
      <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
      {{-- SweetAlert2 --}}
      <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
      <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
      <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />


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
        .hdng{font-size:1.5em;color:#00529A;font-weight:bold;}
      </style>
  </head>

  <body>

    {{-- Sidebar --}}
    <aside class="sidebar text-white">
      {{-- Top --}}
      <div class="text-center mb-4">
        <img src="{{ asset('images/patientcare-logo-white.png') }}" class="logo img-fluid mb-3" alt="Logo">
        <div class="avatar"></div>
        <strong>{{ Auth::user()->username ?? 'OR User' }}</strong><br>
        <small>OR ID: {{ Auth::id() }}</small>
      </div>

      {{-- Navigation --}}
      <nav class="nav flex-column mb-4">
        <a href="{{ route('nurse.dashboard') }}"
           class="nav-link d-flex align-items-center mb-2 {{ request()->routeIs('operating.dashboard') ? 'active' : '' }}">
          <i class="fas fa-home fa-lg me-2"></i>
          <span>Dashboard</span>
        </a>

        <div class="section-label">Patient Management</div>

        <a href="{{ route('nurse.patients.index') }}" 
           class="nav-link d-flex align-items-center mb-2 {{ request()->routeIs('nurse.request.create') ? 'active' : '' }}">
            <i class="fas fa-procedures fa-lg me-2"></i>
            <span>All Patients</span>
        </a>

        <a href="{{ route('nurse.requests.history') }}"
           class="nav-link d-flex align-items-center mb-2 {{ request()->routeIs('nurse.requests.history') ? 'active' : '' }}">
          <i class="fa-solid fa-clock-rotate-left fa-lg me-2"></i>
          <span>Request History</span>
        </a>
      </nav>

      {{-- Footer --}}
      <div class="footer text-center">
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="btn btn-sm btn-outline-light w-100 text-start mb-3">
            <i class="fas fa-sign-out-alt me-2"></i> Logout
          </button>
        </form>
        <small>PatientCare © {{ date('Y') }}</small><br>
      </div>
    </aside>

    {{-- Main Content --}}
    <main>
      @yield('content')
    </main>

    {{-- Bootstrap JS --}}
      <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    @stack('scripts')

  </body>
</html>
