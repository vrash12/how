@extends('layouts.admission')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-primary mb-1">
                <i class="fas fa-bed me-2"></i>Change Room & Bed
            </h3>
            <p class="text-muted mb-0">
                <strong>{{ $patient->patient_first_name }} {{ $patient->patient_last_name }}</strong>
            </p>
        </div>
        <a href="{{ route('admission.patients.show', $patient->patient_id) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back
        </a>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body">
                    @if(isset($currentRoom) && isset($currentBed))
                    <div class="alert alert-info mb-4">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-info-circle me-3 fs-4"></i>
                            <div>
                                <strong>Current Assignment:</strong> Room {{ $currentRoom->room_number ?? 'Unknown' }}, Bed {{ $currentBed ?? 'Unknown' }}
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    <form action="{{ route('admission.patients.change-room-bed', $patient->patient_id) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        
                        <div class="mb-4">
                            <h5 class="mb-3 fw-bold">Select Room</h5>
                            <select id="roomSelect" name="room_id" class="form-select form-select-lg" required>
                                <option value="">-- Choose a Room --</option>
                                @php
                                    // Get rooms with active available beds only
                                    $latestBedIds = DB::table('beds')
                                        ->select(DB::raw('MAX(bed_id) as bed_id'))
                                        ->groupBy('room_id', 'bed_number')
                                        ->pluck('bed_id')
                                        ->toArray();
                                    
                                    $availableRooms = \App\Models\Room::whereHas('beds', function($query) use ($latestBedIds) {
                                        $query->whereIn('bed_id', $latestBedIds)
                                              ->where('status', 'available');
                                    })
                                    ->with(['beds' => function($query) use ($latestBedIds) {
                                        $query->whereIn('bed_id', $latestBedIds)
                                              ->where('status', 'available');
                                    }])
                                    ->orderBy('room_number')
                                    ->get();
                                @endphp
                                @forelse($availableRooms as $room)
                                    @php
                                      $availableBedCount = $room->beds->count();
                                    @endphp
                                    <option value="{{ $room->room_id }}"
                                        {{ old('room_id') == $room->room_id ? 'selected' : '' }}>
                                        Room {{ $room->room_number }} ({{ $availableBedCount }} bed{{ $availableBedCount > 1 ? 's' : '' }} available)
                                    </option>
                                @empty
                                    <option value="" disabled>No rooms with available beds</option>
                                @endforelse
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <h5 class="mb-3 fw-bold">Select Bed</h5>
                            <select id="bedSelect" name="bed_id" class="form-select form-select-lg" required disabled>
                                <option value="">Please select a room first</option>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Reason for Change</label>
                            <textarea name="change_reason" class="form-control" rows="2" placeholder="Optional"></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admission.patients.show', $patient->patient_id) }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                                <i class="fas fa-save me-1"></i> Change Room & Bed
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// BASIC JS TEST
console.log('TESTING SCRIPT SECTION');
alert('Script test alert - this should appear');

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded');
    
    // Get form elements
    const roomSelect = document.getElementById('roomSelect');
    const bedSelect = document.getElementById('bedSelect');
    const submitBtn = document.getElementById('submitBtn');
    
    if (!roomSelect || !bedSelect || !submitBtn) {
        console.error('Elements not found!');
        return;
    }
    
    // Room change handler - SIMPLE VERSION
    roomSelect.onchange = function() {
        const roomId = this.value;
        if (!roomId) {
            bedSelect.innerHTML = '<option value="">Please select a room first</option>';
            bedSelect.disabled = true;
            submitBtn.disabled = true;
            return;
        }
        
        // Simple AJAX with callback for better compatibility
        const xhr = new XMLHttpRequest();
        xhr.open('GET', `/admission/rooms/${roomId}/beds`);
        xhr.onload = function() {
            if (xhr.status === 200) {
                const beds = JSON.parse(xhr.responseText);
                
                // Clear and prepare bed dropdown
                bedSelect.innerHTML = '';
                bedSelect.appendChild(new Option('Choose a bed...', ''));
                
                if (beds && beds.length > 0) {
                    beds.forEach(bed => {
                        const label = `Bed ${bed.bed_number}` + (bed.rate ? ` - ₱${parseFloat(bed.rate).toFixed(2)}` : '');
                        const option = new Option(label, bed.bed_id);
                        bedSelect.appendChild(option);
                    });
                    
                    // Enable bed dropdown
                    bedSelect.disabled = false;
                } else {
                    bedSelect.appendChild(new Option('No available beds', ''));
                    bedSelect.disabled = true;
                }
            } else {
                bedSelect.innerHTML = '<option value="">Error loading beds</option>';
                bedSelect.disabled = true;
            }
            
            submitBtn.disabled = true;
        };
        xhr.send();
    };
    
    // Bed change handler
    bedSelect.onchange = function() {
        submitBtn.disabled = !this.value;
    };
    
    // Initialize if room already selected
    if (roomSelect.value) {
        roomSelect.onchange();
    }
});
</script>
@endpush

<style>
.form-select-lg {
    font-size: 1.1rem;
    padding: 0.75rem 1.25rem;
}
</style>
