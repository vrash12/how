{{-- resources/views/admin/resources/index.blade.php --}}
@extends('layouts.admin')

@section('content')

<div class="card shadow-sm border-0 rounded-4 mb-4">
  <div class="card-header bg-white d-flex align-items-center justify-content-between rounded-top-4 border-0">
    <div>
      <h5 class="fw-bold mb-1">Rooms & Beds</h5>
      <p class="text-muted mb-0 small">Manage hospital rooms and beds.</p>
    </div>
    <button class="btn btn-primary rounded-pill px-4 py-2 fw-semibold d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addRoomModal">
      <i class="fas fa-door-open"></i> <span>Add Room</span>
    </button>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Room #</th>
            <th>Total Beds</th>
            <th>Available Beds</th>
            <th>Beds (status)</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rooms as $room)
            @php
              $total     = $room->beds->count();
              $available = $room->beds->where('status','available')->count();
            @endphp
            <tr>
              <td class="fw-semibold">{{ $room->room_number }}</td>
              {{-- <td>{{ $room->department->department_name }}</td> --}}
              <td>{{ $total }}</td>
              <td>{{ $available }}</td>
              <td>
                <ul class="mb-0 ps-3">
                  @foreach($room->beds as $bed)
                    <li class="small d-flex align-items-center gap-2 mb-1">
                      <span class="fw-semibold">{{ $bed->bed_number }}</span>
                      <span class="badge rounded-pill bg-{{ $bed->status==='available'?'success':'secondary' }} bg-opacity-75">{{ ucfirst($bed->status) }}</span>
                      {{-- <a href="{{ route('admin.resources.edit',['type'=>'bed','id'=>$bed->bed_id]) }}" class="btn btn-outline-primary btn-sm rounded-pill px-3 d-flex align-items-center gap-1" title="Edit Bed"><i class="fas fa-pen"></i> <span>Edit</span></a> --}}
                    </li>
                  @endforeach
                </ul>
              </td>
              <td>
                <div class="d-flex gap-2">
                  {{-- <a href="{{ route('admin.resources.edit',['type'=>'room','id'=>$room->room_id]) }}" class="btn btn-outline-primary btn-sm rounded-pill px-3 d-flex align-items-center gap-1" title="Edit Room"><i class="fas fa-pen"></i> <span>Edit</span></a> --}}
                  <form action="{{ route('admin.resources.destroy',['type'=>'room','id'=>$room->room_id]) }}" method="POST" class="d-inline">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-danger btn-sm rounded-pill px-3 d-flex align-items-center gap-1" onclick="return confirm('Delete this room and its beds?')" title="Delete Room"><i class="fas fa-trash"></i> <span>Delete</span></button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center text-muted py-4">
                <i class="fa-solid fa-circle-info me-1"></i> No rooms found
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

{{-- Add Room Modal --}}
<div class="modal fade" id="addRoomModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
  <form class="modal-content" action="{{ route('admin.resources.store') }}" method="POST" autocomplete="off">
      @csrf
      <input type="hidden" name="type" value="room">
      <div class="modal-header">
        <h5 class="modal-title">New Room</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Department</label>
          <select name="department_id" class="form-select" required>
            <option value="">Select…</option>
            @foreach($departments as $d)
              <option value="{{ $d->department_id }}">{{ $d->department_name }}</option>
            @endforeach
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Room Number</label>
          <input name="room_number" class="form-control" placeholder="e.g. 101A" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Capacity (number of beds)</label>
          <input name="capacity" type="number" min="1" value="1" class="form-control" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Daily Rate (₱ per bed)</label>
          <input name="rate" type="number" step="0.01" min="0" value="0" class="form-control" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Status</label>
          <select name="status" class="form-select" required>
            <option value="available">Available</option>
            <option value="unavailable">Unavailable</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
  <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
  <button class="btn btn-primary">Create Room</button>
      </div>
    </form>
  </div>
</div>

</div>


@endsection

@section('scripts')
<script>
    // Check for success message
    @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: '{{ session('success') }}',
            timer: 3000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    @endif

    // Check for error message
    @if(session('error'))
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: '{{ session('error') }}',
            confirmButtonText: 'OK',
            confirmButtonColor: '#dc3545'
        });
    @endif

    // Replace default confirm dialog for delete buttons
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('form button[onclick*="confirm"]').forEach(button => {
            button.removeAttribute('onclick');
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const form = this.closest('form');
                
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'This action cannot be undone!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    });
</script>
@endsection
