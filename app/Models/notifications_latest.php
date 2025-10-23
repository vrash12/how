<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class notifications_latest extends Model
{
    use HasFactory;

    // If your table name is not the plural of the model name
    protected $table = 'notifications_latest';

    // Fields that can be mass assigned
    protected $fillable = [
        'type',
        'sendTo_id',
        'from_name',
        'read',
        'message',
        'sendTouser_type',
        'popped_up',
        'idReference',
         'titleReference',
         'category',
    ];
}
