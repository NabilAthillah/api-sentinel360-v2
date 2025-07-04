<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    use HasUuids;

    protected $table = 'sites';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $guarded = [];

    public function routes()
    {
        return $this->hasMany(Route::class, 'id_site', 'id');
    }

    public function employees()
    {
        return $this->hasMany(SIteUser::class, 'id_user', 'id');
    }

    public function incident()
    {
        return $this->hasMany(Incident::class);
    }
}

