<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SOPDocument extends Model
{
    use HasUuids;

    protected $table = 'sop_documents';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $guarded = [];
}
