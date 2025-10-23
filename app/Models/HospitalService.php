<?php
// app/Models/HospitalService.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HospitalService extends Model
{
    protected $table = 'hospital_services';
    protected $primaryKey = 'service_id';
    public $timestamps = false; // adjust if you have created_at/updated_at

    protected $fillable = [
        'service_name',
        'price',
        'quantity',
        'description',
        'service_type',
        'needs_prescription',
    ];

    public function department()
    {
        return $this->belongsTo(\App\Models\Department::class, 'department_id', 'department_id');
    }

    public static function findOrLog($id)
    {
        $service = self::find($id);
        if (!$service) {
            \Log::error("HospitalService not found", ['service_id' => $id]);
        }
        return $service;
    }

    public function scopeMedications($query)
    {
        return $query->where('service_type', 'medication');
    }

    public function scopeWithoutPrescription($query)
    {
        return $query->where('needs_prescription', false);
    }

    public function scopeWithPrescription($query)
    {
        return $query->where('needs_prescription', true);
    }
}
