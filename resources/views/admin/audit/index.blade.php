<!-- resources/views/admin/audit/index.blade.php -->
@extends('layouts.admin')

@section('content')
<div class="container-fluid p-4">
    <h4 class="mb-3">🕵️ User Activity Audit Trail</h4>
    
    {{-- Statistics Cards --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-4 col-md-6">
            <div class="card border">
                <div class="card-body text-center">
                    <i class="fa-solid fa-chart-line fa-2x text-primary mb-2"></i>
                    <div class="text-muted small">Total Actions</div>
                    <h5 class="mb-0">{{ number_format($totalActions) }}</h5>
                    @if(isset($filteredActions) && $filteredActions != $totalActions)
                        <div class="text-muted small">Filtered: {{ number_format($filteredActions) }}</div>
                    @endif
                </div>
            </div>
        </div>
        {{-- <div class="col-lg-4 col-md-6">
            <div class="card border">
                <div class="card-body">
                    <div class="text-muted small mb-2">Actions</div>
                    @foreach($actionStats as $action)
                        <div class="d-flex justify-content-between small">
                            <span>{{ ucfirst($action->action) }}</span>
                            <span class="badge bg-secondary">{{ $action->count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="card border">
                <div class="card-body">
                    <div class="text-muted small mb-2">By Module</div>
                    @foreach($moduleStats as $module)
                        <div class="d-flex justify-content-between small">
                            <span>{{ ucfirst(str_replace('_', ' ', $module->module)) }}</span>
                            <span class="badge bg-info">{{ $module->count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div> --}}
    </div>

    {{-- Filters
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">User</label>
                        <input type="text" class="form-control" name="user_search" 
                               value="{{ request('user_search') }}" placeholder="Username">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Action</label>
                        <select class="form-select" name="action">
                            <option value="">All Actions</option>
                            <option value="charge" {{ request('action') == 'charge' ? 'selected' : '' }}>Charge</option>
                            <option value="complete" {{ request('action') == 'complete' ? 'selected' : '' }}>Complete</option>
                            <option value="update" {{ request('action') == 'update' ? 'selected' : '' }}>Update</option>
                            <option value="delete" {{ request('action') == 'delete' ? 'selected' : '' }}>Delete</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Module</label>
                        <select class="form-select" name="module">
                            <option value="">All Modules</option>
                            <option value="doctor_medication" {{ request('module') == 'doctor_medication' ? 'selected' : '' }}>Doctor - Medication</option>
                            <option value="doctor_lab" {{ request('module') == 'doctor_lab' ? 'selected' : '' }}>Doctor - Lab</option>
                            <option value="doctor_operating_room" {{ request('module') == 'doctor_operating_room' ? 'selected' : '' }}>Doctor - OR</option>
                            <option value="pharmacy" {{ request('module') == 'pharmacy' ? 'selected' : '' }}>Pharmacy</option>
                            <option value="laboratory" {{ request('module') == 'laboratory' ? 'selected' : '' }}>Laboratory</option>
                            <option value="operating_room" {{ request('module') == 'operating_room' ? 'selected' : '' }}>Operating Room</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Patient</label>
                        <input type="text" class="form-control" name="patient_search" 
                               value="{{ request('patient_search') }}" placeholder="Patient name">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">From Date</label>
                        <input type="date" class="form-control" name="date_from" 
                               value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">To Date</label>
                        <input type="date" class="form-control" name="date_to" 
                               value="{{ request('date_to') }}">
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-search"></i> Filter
                    </button>
                    <a href="{{ route('admin.audit.index') }}" class="btn btn-secondary">
                        <i class="fa fa-refresh"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div> --}}

    {{-- Audit Trail Table --}}
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Module</th>
                            <th>Patient</th>
                            <th style="max-width: 300px; width: 30%;">Description</th>
                            <th class="text-end">Amount</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($auditLogs as $log)
                            <tr class="{{ $log->action == 'otc_sale' ? 'table-info' : '' }}">
                                <td class="small">
                                    <span class="audit-time-toggle" style="cursor:pointer"
                                          data-time="{{ $log->created_at->format('m/d H:i') }}">
                                        {{ $log->created_at->diffForHumans() }}
                                    </span>
                                </td>
                                <td>
                                    <strong>{{ $log->username }}</strong><br>
                                    <small class="text-muted">{{ $log->user_role }}</small>
                                </td>
                                <td>
                                    @if($log->action == 'charge')
                                        <span class="badge bg-warning">💰 Charge</span>
                                    @elseif($log->action == 'complete')
                                        <span class="badge bg-success">✅ Complete</span>
                                    @elseif($log->action == 'update')
                                        <span class="badge bg-info">✏️ Update</span>
                                    @elseif($log->action == 'delete')
                                        <span class="badge bg-danger">🗑️ Delete</span>
                                    @elseif($log->action == 'otc_sale')
                                        <span class="badge bg-primary">🏪 OTC Sale</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $log->action }}</span>
                                    @endif
                                </td>
                                <td>
                                    <small>{{ ucfirst(str_replace('_', ' ', $log->module)) }}</small>
                                </td>
                                <td>
                                    @if($log->patient_name)
                                        <strong>{{ $log->patient_name }}</strong><br>
                                        <small class="text-muted">ID: {{ $log->patient_id }}</small>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="small text-wrap" style="max-width: 300px; overflow-wrap: break-word;">{{ $log->description }}</td>
                                <td class="text-end">
                                    @if($log->amount_involved)
                                        @if($log->action == 'delete')
                                            <strong class="text-danger">-₱{{ number_format($log->amount_involved, 2) }}</strong>
                                        @else
                                            <strong class="text-success">₱{{ number_format($log->amount_involved, 2) }}</strong>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="small text-muted">{{ $log->ip_address }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4">No audit records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted small">
                    Showing {{ $auditLogs->firstItem() ?? 0 }} to {{ $auditLogs->lastItem() ?? 0 }} of 
                    {{ $auditLogs->total() }} entries
                </div>
                <div>
                    <select class="form-select form-select-sm d-inline-block me-2" style="width: auto;" id="page-size-selector" onchange="changePageSize(this.value)">
                        <option value="10" {{ isset($pageSize) && $pageSize == 10 ? 'selected' : '' }}>10 per page</option>
                        <option value="25" {{ !isset($pageSize) || $pageSize == 25 ? 'selected' : '' }}>25 per page</option>
                        <option value="50" {{ isset($pageSize) && $pageSize == 50 ? 'selected' : '' }}>50 per page</option>
                        <option value="100" {{ isset($pageSize) && $pageSize == 100 ? 'selected' : '' }}>100 per page</option>
                    </select>
                    {{ $auditLogs->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Add some additional styling for better table display */
    .table td {
        vertical-align: middle;
    }
    
    /* Set max width for the entire table */
    .table-responsive {
        max-width: 100%;
    }
    
    /* Better pagination display */
    .pagination {
        margin-bottom: 0;
    }
</style>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle time format
    document.querySelectorAll('.audit-time-toggle').forEach(function(el) {
        el.addEventListener('click', function() {
            let current = el.textContent.trim();
            let alt = el.getAttribute('data-time');
            el.setAttribute('data-time', current);
            el.textContent = alt;
        });
    });
});

// Function to handle page size changes
function changePageSize(size) {
    // Get current URL
    let url = new URL(window.location.href);
    // Update or add the page_size parameter
    url.searchParams.set('page_size', size);
    // Reset to first page when changing page size
    url.searchParams.delete('page');
    // Navigate to the updated URL
    window.location.href = url.toString();
}
</script>
@endpush