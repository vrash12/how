<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NurseRequest extends Model
{
    protected $table = 'nurse_requests';

    protected $fillable = [
        'patient_id',
        'nurse_id',
        'doctor_id',
        'type',
        'payload',
        'status',
    ];

    // Relationships
    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function nurse()
    {
        return $this->belongsTo(User::class, 'nurse_id', 'user_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id', 'doctor_id');
    }
}