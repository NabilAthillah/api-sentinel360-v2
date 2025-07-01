<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployeeDocument;
use Illuminate\Http\Request;

class EmployeeDocumentController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => EmployeeDocument::all()
        ]);
    }
}
