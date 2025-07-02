<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class EmployeeDocumentPivot extends Model
{
    use HasUuids;

    protected $table = 'employee_document';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $guarded = [];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'id_employee', 'id');
    }
    
    public function employee_document()
    {
        return $this->belongsTo(EmployeeDocument::class, 'id_document', 'id');
    }
}
