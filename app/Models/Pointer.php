<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Pointer extends Model
{
    use HasUuids;

    protected $table = 'pointers';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $guarded = [];

    public function route()
    {
        return $this->belongsTo(Route::class, 'id_route', 'id');
    }
}
