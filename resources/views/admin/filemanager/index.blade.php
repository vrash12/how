<!-- filepath: resources/views/admin/hospital_services/index.blade.php -->
@extends('layouts.admin')

@section('content')

<div class="card shadow-sm border-0 rounded-4 mb-4">
  <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
    <h3 class="fw-bold mb-0">Hospital Services</h3>
    <a href="{{ route('admin.hospital_services.create') }}" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold d-flex align-items-center gap-2">
      <i class="fas fa-plus"></i> Add Service
    </a>
  </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-3">
  <div class="card-body p-3">
    <form method="GET" action="{{ route('admin.hospital_services.index') }}" class="row g-2 align-items-end">
      <div class="col-md-6">
        <div class="input-group">
          <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search services…">
          <button class="btn btn-outline-primary" type="submit">Search</button>
        </div>
      </div>
      <div class="col-md-4">
        <label for="prescription-filter" class="form-label small">Prescription Filter</label>
        <select name="prescription" id="prescription-filter" class="form-select" onchange="this.form.submit()">
          <option value="">All Medications</option>
          <option value="required" {{ request('prescription') === 'required' ? 'selected' : '' }}>Prescription Required</option>
          <option value="otc" {{ request('prescription') === 'otc' ? 'selected' : '' }}>Over-the-Counter (OTC)</option>
        </select>
      </div>
      <div class="col-md-2">
        @if(request('q') || request('prescription'))
          <a href="{{ route('admin.hospital_services.index') }}" class="btn btn-outline-secondary w-100">Clear Filters</a>
        @endif
      </div>
    </form>
  </div>
</div>

@if(session('success'))
  <div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i>
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
@endif

<!-- Improved Tabs with Better Styling -->
<div class="card shadow-sm border-0 rounded-4 mb-4">
  <div class="card-header bg-primary p-0">
    <ul class="nav nav-tabs card-header-tabs" id="serviceTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active px-4 py-3" id="medication-tab" data-bs-toggle="tab" data-bs-target="#medication" type="button" role="tab" aria-selected="true">
          <i class="fas fa-pills me-2"></i>Medications
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link px-4 py-3" id="lab-tab" data-bs-toggle="tab" data-bs-target="#lab" type="button" role="tab" aria-selected="false">
          <i class="fas fa-flask me-2"></i>Lab Tests
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link px-4 py-3" id="operation-tab" data-bs-toggle="tab" data-bs-target="#operation" type="button" role="tab" aria-selected="false">
          <i class="fas fa-procedures me-2"></i>Operations
        </button>
      </li>
    </ul>
  </div>
  
  <div class="card-body p-0">
    <div class="tab-content" id="serviceTabsContent">
      <div class="tab-pane fade show active p-3" id="medication" role="tabpanel" aria-labelledby="medication-tab">
        <div style="max-height: 600px; overflow-y: auto;" class="table-responsive">
          @include('admin.filemanager._service_table', ['services' => $medications, 'type' => 'Medication'])
        </div>
      </div>
      
      <div class="tab-pane fade p-3" id="lab" role="tabpanel" aria-labelledby="lab-tab">
        <div style="max-height: 600px; overflow-y: auto;" class="table-responsive">
          @include('admin.filemanager._service_table', ['services' => $labs, 'type' => 'Lab'])
        </div>
      </div>
      
      <div class="tab-pane fade p-3" id="operation" role="tabpanel" aria-labelledby="operation-tab">
        <div style="max-height: 600px; overflow-y: auto;" class="table-responsive">
          @include('admin.filemanager._service_table', ['services' => $operations, 'type' => 'Operation'])
        </div>
      </div>
    </div>
  </div>
</div>

@push('styles')
<style>
  /* Custom tab styling */
  .nav-tabs .nav-link {
    color: #495057;
    font-weight: 600;
    border: none;
    border-bottom: 2px solid transparent;
  }
  
  .nav-tabs .nav-link:hover {
    border-color: transparent;
    background-color: rgba(0,0,0,0.03);
  }
  
  .nav-tabs .nav-link.active {
    color: #0d6efd;
    border-bottom: 2px solid #0d6efd;
    background-color: rgba(13, 110, 253, 0.05);
  }
  
  /* Make sure table headers stay visible when scrolling */
  .table-responsive {
    position: relative;
  }
  
  .table thead th {
    position: sticky;
    top: 0;
    background-color: #f8f9fa;
    z-index: 10;
  }
  
  /* Improve badge visibility */
  .badge {
    font-size: 0.85em;
    padding: 0.35em 0.65em;
  }
</style>
@endpush

@push('scripts')
<script>
  // Maintain active tab on page reload
  document.addEventListener('DOMContentLoaded', function() {
    // Get the active tab from localStorage or use default
    const activeTab = localStorage.getItem('activeServiceTab') || 'medication-tab';
    
    // Activate the tab
    const tab = document.getElementById(activeTab);
    if (tab) {
      const bsTab = new bootstrap.Tab(tab);
      bsTab.show();
    }
    
    // Store the active tab when changed
    const tabs = document.querySelectorAll('button[data-bs-toggle="tab"]');
    tabs.forEach(tab => {
      tab.addEventListener('shown.bs.tab', function(event) {
        localStorage.setItem('activeServiceTab', event.target.id);
      });
    });
  });
</script>
@endpush
@endsection