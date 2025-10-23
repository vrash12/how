<?php
// app/Helpers/UserAuditHelper.php

namespace App\Helpers;

use App\Models\UserAuditTrail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class UserAuditHelper
{
    public static function log($action, $module, $description, $options = [])
    {
        $user = Auth::user();
        
        if (!$user) return; // Skip if no user logged in

        $auditData = [
            'user_id' => $user->user_id,
            'username' => $user->username,
            'user_role' => $user->role,
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ];

        // Merge any additional options
        $auditData = array_merge($auditData, $options);

        UserAuditTrail::create($auditData);
    }

    public static function logCharge($patientId, $amount, $serviceName, $module)
    {
        $patient = \App\Models\Patient::find($patientId);
        $patientName = $patient ? "{$patient->patient_first_name} {$patient->patient_last_name}" : 'Unknown';

        self::log('charge', $module, "Added charge: {$serviceName} (₱" . number_format($amount, 2) . ")", [
            'affected_table' => 'service_assignments',
            'patient_id' => $patientId,
            'patient_name' => $patientName,
            'amount_involved' => $amount,
        ]);
    }

    public static function logComplete($patientId, $serviceName, $module, $recordId = null)
    {
        $patient = \App\Models\Patient::find($patientId);
        $patientName = $patient ? "{$patient->patient_first_name} {$patient->patient_last_name}" : 'Unknown';

        self::log('complete', $module, "Completed: {$serviceName}", [
            'affected_table' => 'service_assignments',
            'affected_record_id' => $recordId,
            'patient_id' => $patientId,
            'patient_name' => $patientName,
        ]);
    }

    public static function logEdit($tableName, $recordId, $oldData, $newData, $description)
    {
        self::log('update', 'admin', $description, [
            'affected_table' => $tableName,
            'affected_record_id' => $recordId,
            'old_data' => $oldData,
            'new_data' => $newData,
        ]);
    }

    public static function logDelete($tableName, $recordId, $description)
    {
        self::log('delete', 'admin', $description, [
            'affected_table' => $tableName,
            'affected_record_id' => $recordId,
        ]);
    }
}