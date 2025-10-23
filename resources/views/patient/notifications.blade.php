{{-- resources/views/patient/notifications.blade.php --}}
@extends('layouts.patients')

@section('content')

  <div class="mb-3">
    <h4 class="fw-bold">Notifications</h4>
    <p class="text-muted">Stay updated on your billing and account activities.</p>
  </div>

  <div class="alert alert-warning">
    <i class="fa-solid fa-circle-info me-2"></i>
    <strong>Important!</strong> Do not share this information with anyone.
  </div>

  {{-- Filter & Mark-All --}}
  <div class="row mb-3 align-items-center">
    <div class="col-auto">
      <form method="GET" class="d-flex align-items-center">
        <label class="me-2 mb-0 fw-semibold">Filter:</label>
        <select name="filter" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="all"   {{ $filter==='all'    ? 'selected' : '' }}>All</option>
          <option value="read"  {{ $filter==='read'   ? 'selected' : '' }}>Read</option>
          <option value="unread"{{ $filter==='unread' ? 'selected' : '' }}>Unread</option>
        </select>
      </form>
    </div>
    <div class="col text-end">
      <form action="{{ route('notifications.markAllRead') }}" method="POST">
        @csrf
        <button class="btn btn-sm btn-outline-secondary">Mark All as Read</button>
      </form>
    </div>
  </div>

  {{-- Notifications Table --}}
  <div class="card shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive" style="max-height: 60vh; overflow-y: auto;">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light sticky-top">
            <tr>
              <th class="text-center">Type</th>
              <th>Message</th>
              <th>Date</th>
              <th>Time</th>
              <th>From</th>
              <th class="text-center">Status</th>
            </tr>
          </thead>
          <tbody>
            @forelse($notifications as $n)
              <tr class="{{ $n->read == '0' ? 'table-warning' : '' }}"> {{-- Yellow for unread, white for read --}}
                <td class="text-center">{{ $n->type ?? 'Notification' }}</td>
                <td>@php
                      echo $n->message ? htmlspecialchars_decode($n->message) : '—';
                    @endphp
                </td>
                <td>{{ $n->created_at->format('M d, Y') }}</td>
                <td>{{ $n->created_at->format('g:i A') }}</td>
                <td>{{ $n->from_name }}</td>
                <td class="text-center">
                  <span class="badge bg-{{ $n->read == '1' ? 'success' : 'danger' }}">
                    {{ $n->read == '1' ? 'Read' : 'Unread' }}
                  </span>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center text-muted">No notifications found.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

@endsection

@push('styles')
<style>
  /* Modern and clean table design */
  .table thead th {
    position: sticky;
    top: 0;
    z-index: 1020;
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
  }

  .table tbody tr {
    transition: background-color 0.2s ease-in-out;
  }

  .table tbody tr:hover {
    background-color: #f1f3f5;
  }

  .table tbody tr.table-warning:hover {
    background-color: #ffe8a1;
  }

  .table tbody td {
    vertical-align: middle;
  }

  .badge {
    font-size: 0.85rem;
    padding: 0.4em 0.6em;
  }

  .alert {
    border-radius: 0.5rem;
  }
</style>
@endpush

{{-- DETAILS / TIMELINE MODAL --}}
<div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Charge Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <ul class="nav nav-tabs mb-3">
          <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-timeline" type="button">
              Timeline
            </button>
          </li>
          <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-details" type="button">
              Details
            </button>
          </li>
        </ul>
        <div class="tab-content">
          <div class="tab-pane fade show active" id="tab-timeline">
            <p class="text-muted">Loading…</p>
          </div>
          <div class="tab-pane fade" id="tab-details">
            <p class="text-muted">Loading…</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const modalEl = document.getElementById('detailsModal');
  const bsModal = new bootstrap.Modal(modalEl);

  document.querySelectorAll('.btn-details').forEach(btn => {
    btn.addEventListener('click', async () => {
      // show the modal immediately
      bsModal.show();

      // loading placeholders
      document.getElementById('tab-timeline').innerHTML = '<p class="text-muted">Loading…</p>';
      document.getElementById('tab-details').innerHTML  = '<p class="text-muted">Loading…</p>';

      try {
        const res  = await fetch(btn.dataset.url);
        const html = await res.text();
        const doc  = new DOMParser().parseFromString(html, 'text/html');

        // pull out the two sections from the fetched page
        const tl = doc.querySelector('#charge-trace-timeline');
        const dt = doc.querySelector('#charge-trace-details');

        document.getElementById('tab-timeline').innerHTML = tl
          ? tl.innerHTML
          : '<p class="text-muted">No timeline available.</p>';

        document.getElementById('tab-details').innerHTML = dt
          ? dt.innerHTML
          : '<p class="text-muted">No details available.</p>';

      } catch (err) {
        document.getElementById('tab-timeline').innerHTML = '<p class="text-danger">Error loading timeline.</p>';
        document.getElementById('tab-details').innerHTML  = '<p class="text-danger">Error loading details.</p>';
      }
    });
  });
});
</script>
@endpush
