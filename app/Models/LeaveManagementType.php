<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class LeaveManagementType extends Model
{
    use HasUuids;

    protected $table = 'leave_managements_type';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $guarded = [];

    public function LeaveManagement()
    {
        return $this->belongsTo(LeaveManagement::class, 'id_leave_management', 'id');
    }
}
