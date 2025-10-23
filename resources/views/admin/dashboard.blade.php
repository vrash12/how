@extends('layouts.admin')

@section('content')
    {{-- Header --}}
    <div class="mb-4">
        <h4 class="fw-bold">🏥 Users and Resources Management</h4>
        <p class="text-muted">Welcome to Admin! Manage Users and Resources for Hospital Operations.</p>
    </div>
    
    {{-- Stats / Metrics --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-4 col-md-6 col-sm-12">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-body d-flex align-items-center">
                    <i class="fa-solid fa-users fa-2x text-primary me-4"></i>
                    <div>
                        <div class="text-muted small">Total Active Users</div>
                        <h4 class="fw-bold mb-0">{{ $totalActiveUsers }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-12">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-body d-flex align-items-center">
                    <i class="fa-solid fa-door-open fa-2x text-success me-4"></i>
                    <div>
                        <div class="text-muted small">Total Created Rooms</div>
                        <h4 class="fw-bold mb-0">{{ $totalCreatedRooms }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-12">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-body d-flex align-items-center">
                    <i class="fa-solid fa-bed fa-2x text-warning me-4"></i>
                    <div>
                        <div class="text-muted small">Total Created Beds</div>
                        <h4 class="fw-bold mb-0">{{  $totalCreatedBeds }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Recently Created Users --}}
    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-header bg-white d-flex align-items-center border-0 rounded-top-3">
            <i class="fa-solid fa-clock-rotate-left me-2 text-secondary"></i>
            <h6 class="mb-0">Recently Created Users</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentUsers as $i => $user)
                            <tr>
                                <td class="fw-bold text-muted">{{ $i + 1 }}</td>
                                <td>
                                    <span class="fw-semibold">{{ $user->username }}</span>
                                </td>
                                <td>
                                    <span class="badge rounded-pill bg-light text-dark border">{{ $user->email }}</span>
                                </td>
                                <td>
                                    <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary border">{{ $user->role }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    <i class="fa-solid fa-circle-info me-1"></i> No users found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- <script>
        $('.myTable').DataTable({
    dom: '<"top d-flex justify-content-between align-items-center mb-3"fB>rt' +
         '<"bottom d-flex justify-content-between align-items-center mt-3"i p>',
    buttons: [
        {
            extend: 'csv',
            text: '<i class="fa-solid fa-file-csv"></i> CSV'
        },
        {
            extend: 'excel',
            text: '<i class="fa-solid fa-file-excel"></i> Excel'
        },
        {
            extend: 'pdf',
            text: '<i class="fa-solid fa-file-pdf"></i> PDF'
        },
       
        // {
        //     text: '<i class="fa-solid fa-file-pdf"></i> PDF',
         
        //     action: function (e, dt, node, config) {
        //         window.location.href = "";
        //     }
        // }
    ]
});
    </script> --}}
@endsection
