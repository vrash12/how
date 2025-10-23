{{--resources/views/admin/users/index.blade.php--}}2
@extends('layouts.admin')

@section('content')

@if(session('success'))
<script>
    Swal.fire({
        icon: 'success',
        title: 'Success',
        text: '{{ session('success') }}',
        timer: 2500,
        showConfirmButton: false
    });
</script>
@endif

@if(session('error'))
<script>
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: '{{ session('error') }}'
    });
</script>
@endif

@if($errors->any())
<script>
    Swal.fire({
        icon: 'error',
        title: 'Validation Error',
        html: `{!! implode('<br>', $errors->all()) !!}`
    });
</script>
@endif

<div class="card shadow-sm p-0 border-0 rounded-4 mb-4">
  <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
      <h3 class="fw-bold mb-0">Users Management</h3>
      <p class="text-muted mb-0 small">Always double check the information you will put in to avoid problems later.</p>
    </div>
    <a href="{{ route('admin.users.create') }}" class="btn btn-success rounded-pill px-4 py-2 fw-semibold d-flex align-items-center gap-2">
      <i class="fas fa-plus"></i> New User
    </a>
  </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-3">
  <div class="card-body align-items-center">
    <form method="GET" action="{{ route('admin.users.index') }}" id="searchRoleForm" class="row g-2 align-items-center">
      <div class="col-8">
        <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search users by username or email...">
      </div>
      <div class="col-auto">
        <select name="role" class="form-select" onchange="document.getElementById('searchRoleForm').submit()">
          <option value="">All Roles</option>
          @foreach($roles ?? [] as $r)
            <option value="{{ $r }}" {{ request('role') === $r ? 'selected' : '' }}>{{ ucfirst($r) }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-auto">
        <button class="btn btn-outline-primary" type="submit"><i class="fas fa-search"></i></button>
      </div>
    </form>
  </div>
</div>

<div class="card shadow-sm border-0 rounded-4 mb-4">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width: 50px;">#</th>
            <th>Username</th>
            <th>Email</th>
            <th>Role</th>
            <th colspan="1">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($users as $u)
            <tr>
              <td class="fw-bold text-muted">{{ $u->user_id }}</td>
              <td><span class="fw-semibold">{{ $u->username }}</span></td>
              <td><span class="badge rounded-pill bg-light text-dark border">{{ $u->email }}</span></td>
              <td><span class="badge rounded-pill bg-primary bg-opacity-10 text-primary border">{{ ucfirst($u->role) }}</span></td>
              <td>
                <a href="{{ route('admin.users.edit',$u) }}" class="btn btn-sm btn-outline-primary me-1 rounded-pill" title="Edit"><i class="fas fa-pen me-2"></i><span>Edit</span></a>
                <form method="POST" action="{{ route('admin.users.destroy',$u) }}" class="d-inline delete-form">
                  @csrf @method('DELETE')
                  <button type="button" class="btn btn-sm btn-outline-danger rounded-pill delete-btn" title="Delete"><i class="fas fa-trash"></i> <span>Delete</span></button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center text-muted py-4">
                <i class="fa-solid fa-circle-info me-1"></i> No users found
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @if ($users->hasPages())
    <div class="card-footer bg-white border-0 rounded-bottom-3 py-3">
      {{ $users->withQueryString()->links() }}
    </div>
  @endif
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

    // Check for validation errors
    @if($errors->any())
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            html: '{!! implode("<br>", $errors->all()) !!}',
            confirmButtonText: 'OK',
            confirmButtonColor: '#dc3545'
        });
    @endif

    // Handle delete confirmation with SweetAlert
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const form = this.closest('.delete-form');
                
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'This user will be permanently deleted!',
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
