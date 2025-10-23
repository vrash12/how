<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Bed extends Model
{
    use HasFactory;

    protected $primaryKey = 'bed_id';
    protected $table = 'beds';
    public $timestamps = true;

    protected $fillable = [
        'room_id',
        'bed_number',
        'status',
        'rate',
        'patient_id',
    ];


     protected $casts = [
        'rate'     => 'decimal:2',
    ];

    /**
     * If this bed has no custom rate, return 0.
     */
    public function getDailyRateAttribute(): float
    {
        return $this->rate > 0 ? $this->rate : 0;
    }

    public function room()
    {
        \Log::info('Fetching Room for Bed', [
            'bed_id' => $this->bed_id,
            'room_id' => $this->room_id,
        ]);

        return $this->belongsTo(Room::class, 'room_id', 'room_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    /**
     * Is this bed currently occupied?
     */
    public function isOccupied(): bool
    {
        return $this->patient_id !== null;
    }

    public function releaseBed()
    {
        if ($this->isOccupied()) {
            // Duplicate the current bed record
            $newBed = $this->replicate();
            $newBed->patient_id = null; // Unassign the patient
            $newBed->status = 'available'; // Mark as available
            $newBed->save(); // Save the new bed record

            // Log the release action
            \Log::info('Bed released and replicated', [
                'original_bed_id' => $this->bed_id,
                'new_bed_id' => $newBed->bed_id,
            ]);
        }
    }
}

