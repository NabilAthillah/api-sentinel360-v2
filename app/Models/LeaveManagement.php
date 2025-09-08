<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class LeaveManagement extends Model
{
    use HasUuids;

    protected $table = 'leave_managements';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $guarded = [];

    public function LeaveManagamentType()
    {
        return $this->belongsTo(LeaveManagementType::class, 'id_leave_management_type');
    }
    public function site()
    {
        return $this->belongsTo(Site::class, 'id_site', 'id');
    }
}
