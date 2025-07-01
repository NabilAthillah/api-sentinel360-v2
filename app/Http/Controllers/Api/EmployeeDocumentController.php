<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployeeDocument;
use DB;
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

    public function update(Request $request, $id)
    {
        try {
            //code...
            DB::beginTransaction();

            $document = EmployeeDocument::where('id', $id)->first();

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Oops! Something went wrong'
                ], 404);
            }

            $document->update([
                'name' => $request->name ?? $document->name,
                'status' => $request->status ?? $document->status,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Document updated successfully'
            ], 200);

        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'success' => false,
                'message' => 'Oops! Somtehing went wrong',
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            //code...
            DB::beginTransaction();

            $category = EmployeeDocument::create([
                'name' => $request->name,
            ]);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Oops! Somtehing went wrong'
                ], 500);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Document created successfully'
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'success' => false,
                'message' => 'Oops! Somtehing went wrong'
            ], 500);
        }
    }
}
