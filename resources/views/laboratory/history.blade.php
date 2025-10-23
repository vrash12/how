{{-- filepath: resources/views/laboratory/history.blade.php --}}
@extends('layouts.laboratory')

@section('content')

<div class="container-fluid">

    {{-- Header --}}
    <div class="mb-4">
        <h3 class="fw-bold hdng mb-1">🧪 Laboratory History</h3>
        <p class="text-muted">View the history of completed and cancelled lab requests.</p>
    </div>

    {{-- History Table --}}
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Order No</th>
                        <th>Patient</th>
                        <th>Physician</th>
                        <th>Completion Date</th>
                        <th>Tests</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                {{-- Completed Orders --}}
                @forelse($completedOrders as $order)
                    <tr>
                        <td>
                            <span class="badge bg-light text-dark">
                                {{ str_pad($order->assignment_id, 6, '0', STR_PAD_LEFT) }}
                            </span>
                        </td>
                        <td>
                            <i class="fas fa-user text-primary me-1"></i>
                            {{ $order->patient->full_name 
                                ?? $order->patient->patient_first_name . ' ' . $order->patient->patient_last_name }}
                        </td>
                        <td>
                            <i class="fas fa-user-md text-secondary me-1"></i>
                            {{ $order->doctor->doctor_name ?? '—' }}
                        </td>
                        <td class="text-muted">
                            <span class="completed-at" style="cursor: pointer"
                                data-date="{{ $order->updated_at->format('M d, Y h:i A') }}">
                                {{ $order->updated_at->diffForHumans() }}
                            </span>
                        </td>
                        <td>
                            <span class="badge rounded-pill bg-light text-dark border me-1 mb-1">
                                {{ $order->service->service_name ?? 'N/A' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-success">
                                Completed
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('laboratory.history.show', $order->assignment_id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fa fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-info-circle me-1"></i> No completed lab orders found.
                        </td>
                    </tr>
                @endforelse

                {{-- Cancelled Orders --}}
                @forelse($cancelledOrders as $order)
                    <tr>
                        <td>
                            <span class="badge bg-light text-dark">
                                {{ str_pad($order->assignment_id, 6, '0', STR_PAD_LEFT) }}
                            </span>
                        </td>
                        <td>
                            <i class="fas fa-user text-primary me-1"></i>
                            {{ $order->patient->full_name 
                                ?? $order->patient->patient_first_name . ' ' . $order->patient->patient_last_name }}
                        </td>
                        <td>
                            <i class="fas fa-user-md text-secondary me-1"></i>
                            {{ $order->doctor->doctor_name ?? '—' }}
                        </td>
                        <td class="text-muted">
                            <span class="completed-at" style="cursor: pointer"
                                data-date="{{ $order->updated_at->format('M d, Y h:i A') }}">
                                {{ $order->updated_at->diffForHumans() }}
                            </span>
                        </td>
                        <td>
                            <span class="badge rounded-pill bg-light text-dark border me-1 mb-1">
                                {{ $order->service->service_name ?? 'N/A' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-danger">
                                Cancelled
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('laboratory.history.show', $order->assignment_id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fa fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-info-circle me-1"></i> No cancelled lab orders found.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@endsection
