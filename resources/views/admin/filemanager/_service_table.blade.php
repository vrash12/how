<!-- filepath: resources/views/admin/filemanager/_service_table.blade.php -->
<div class="card shadow-sm border-0 rounded-4 mb-3">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Name</th>
          <th>Price</th>
          <th>Description</th>
          @if($type === 'Medication')
          <th width="120">Prescription</th>
          @endif
          <th width="200">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($services as $service)
          <tr>
            <td class="fw-semibold">{{ $service->service_name }}</td>
            <td>₱{{ number_format($service->price,2) }}</td>
            <td>
              <div class="text-truncate" style="max-width: 300px;" title="{{ $service->description }}">
                {{ $service->description ?: 'No description available' }}
              </div>
            </td>
            @if($type === 'Medication')
            <td class="text-center">
              @if($service->needs_prescription ?? true)
                <span class="badge bg-danger">
                  <i class="fas fa-file-prescription me-1"></i> Required
                </span>
              @else
                <span class="badge bg-success">
                  <i class="fas fa-check-circle me-1"></i> OTC
                </span>
              @endif
            </td>
            @endif
            <td>
              <div class="btn-group">
                <a href="{{ route('admin.hospital_services.edit', $service) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                  <i class="fas fa-pen"></i> Edit
                </a>
                <button type="button" class="btn btn-sm btn-outline-danger delete-btn" 
                        data-service-id="{{ $service->service_id }}"
                        data-service-name="{{ $service->service_name }}"
                        title="Delete">
                  <i class="fas fa-trash"></i> Delete
                </button>
              </div>
              <form id="delete-form-{{ $service->service_id }}" action="{{ route('admin.hospital_services.destroy', $service) }}" method="POST" class="d-none">
                @csrf @method('DELETE')
              </form>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="text-center py-4">
              <div class="d-flex flex-column align-items-center text-muted">
                <i class="fas fa-box-open fa-3x mb-3"></i>
                <p>No {{ strtolower($type) }}s found.</p>
                <a href="{{ route('admin.hospital_services.create') }}" class="btn btn-sm btn-outline-primary">
                  <i class="fas fa-plus me-1"></i> Add New {{ $type }}
                </a>
              </div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@push('scripts')
<script>
  // Use SweetAlert2 for delete confirmation
  document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.delete-btn').forEach(button => {
      button.addEventListener('click', function() {
        const serviceId = this.dataset.serviceId;
        const serviceName = this.dataset.serviceName;
        
        Swal.fire({
          title: 'Delete Service?',
          html: `Are you sure you want to delete <strong>${serviceName}</strong>?<br>This action cannot be undone.`,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#dc3545',
          cancelButtonColor: '#6c757d',
          confirmButtonText: 'Yes, delete it!',
          cancelButtonText: 'Cancel'
        }).then((result) => {
          if (result.isConfirmed) {
            document.getElementById(`delete-form-${serviceId}`).submit();
          }
        });
      });
    });
  });
</script>
@endpush