<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SiteUser extends Model
{
    use HasUuids;

    protected $table = 'site_user';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $guarded = [];

    public function site()
    {
        return $this->belongsTo(Site::class, 'id_site', 'id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'id_employee', 'id');
    }

    public function attendance()
    {
        return $this->hasOne(Attendance::class, 'id_site_employee', 'id');
    }
}
