<?php
// app/Models/UserAuditTrail.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAuditTrail extends Model
{
    protected $table = 'user_audit_trail';
    protected $primaryKey = 'audit_id';

    protected $fillable = [
        'user_id',
        'username',
        'user_role',
        'action',
        'module',
        'affected_table',
        'affected_record_id',
        'patient_id',
        'patient_name',
        'description',
        'old_data',
        'new_data',
        'amount_involved',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
        'amount_involved' => 'decimal:2'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }
}