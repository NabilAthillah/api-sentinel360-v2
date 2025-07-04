<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class IncidentType extends Model
{
    use HasUuids;

    protected $table = 'incident_types';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $guarded = [];

    public function incident()
    {
        return $this->hasMany(Incident::class);
    }
}
