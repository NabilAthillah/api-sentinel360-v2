<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class OccurrenceCategory extends Model
{
    use HasUuids;

    protected $table = 'occurrence_category';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $guarded = [];

    public function occurrences()
    {
        return $this->hasMany(Occurrence::class, 'id_category', 'id');
    }
}
