<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasUuids;

    protected $table = 'employees';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $guarded = [];

    public function reporting()
    {
        return $this->belongsTo(User::class, 'reporting_to', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }

    public function sites()
    {
        return $this->hasMany(SiteUser::class, 'id_employee', 'id');
    }

    public function documents()
    {
        return $this->hasMany(EmployeeDocumentPivot::class, 'id_employee', 'id');
    }
}
