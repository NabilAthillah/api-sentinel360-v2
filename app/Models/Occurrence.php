<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Occurrence extends Model
{
    use HasUuids;

    protected $table = 'occurrences';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $guarded = [];

    public function site()
    {
        return $this->belongsTo(Site::class, 'id_site', 'id');
    }

    public function category()
    {
        return $this->belongsTo(OccurrenceCategory::class, 'id_category', 'id');
    }

    public function reported_by()
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }
}
