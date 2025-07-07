<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Incident extends Model
{
    use HasUuids;

    protected $table = 'incidents';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $guarded = [];

    
    public function site()
    {
        return $this->belongsTo(Site::class, 'site_id', 'id');
    }

    public function incidentType()
    {
        return $this->belongsTo(IncidentType::class, 'incident_type_id', 'id');
    }
}
