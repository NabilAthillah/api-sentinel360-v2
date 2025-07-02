<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use DB;
use Illuminate\Http\Request;

class EmployeeDocumentPivotController extends Controller
{
    public function store(Request $request)
    {
        try {
            //code...
            DB::beginTransaction();

            $employee = Employee::where('id', $request->id_employee)->first();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Document uploaded successfully'
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong'
            ], 500);
        }
    }
}
