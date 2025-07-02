<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeeDocumentPivot;
use DB;
use Illuminate\Http\Request;
use Storage;

class EmployeeDocumentPivotController extends Controller
{
    public function store(Request $request)
    {
        try {
            //code...
            DB::beginTransaction();

            $employee = Employee::where('id', $request->id_employee)->first();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => "Employee not found"
                ], 404);
            }

            $documents = EmployeeDocument::all();

            foreach ($documents as $item) {
                $exists = EmployeeDocumentPivot::where('id_document', $item->id)->where('id_employee', $request->id_employee)->first;

                if ($exists) {
                    if (!Storage::delete($exists->path)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Oops! Something went wrong'
                        ], 500);
                    }

                    $image = $request->document;
                    $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image));
                    $imageName = uniqid() . '.png';
                    Storage::disk('public')->put("sop_doc/images/{$imageName}", $imageData);
                    $pathImage = "dop_doc/images/{$imageName}";
                }
            }

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
