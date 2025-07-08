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

    public function index($id)
    {
        $data = EmployeeDocumentPivot::where('id_employee', $id)->get();

        $data->transform(function ($item) {
            $item->url = asset('storage/' . $item->path); 
            return $item;
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $employee = Employee::find($request->id_employee);
            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => "Employee not found"
                ], 404);
            }

            $document = EmployeeDocument::find($request->id_document);
            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => "Document type not found"
                ], 404);
            }

            if (!$request->document || !preg_match('/^data:(.*);base64,/', $request->document, $matches)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid document format'
                ], 422);
            }

            $mimeType = $matches[1];
            $extension = explode('/', $mimeType)[1] ?? 'bin';

            if ($fileData === false || base64_encode($fileData) !== $base64Data) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or corrupted base64 data.'
                ], 422);
            }
            if ($fileData === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to decode base64 file'
                ], 422);
            }

            $fileName = uniqid() . '.' . $extension;
            $storagePath = "sop_doc/files/{$fileName}";
            Storage::disk('public')->put($storagePath, $fileData);

            // Hapus file lama jika ada
            $existing = EmployeeDocumentPivot::where('id_employee', $employee->id)
                ->where('id_document', $document->id)
                ->first();

            if ($existing) {
                Storage::disk('public')->delete($existing->path);
                $existing->delete();
            }

            // Simpan pivot dokumen
            EmployeeDocumentPivot::create([
                'id_employee' => $employee->id,
                'id_document' => $document->id,
                'path' => $storagePath
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Document uploaded successfully'
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
