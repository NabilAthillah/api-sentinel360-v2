<?php

namespace App\Http\Controllers\Api;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeeDocumentPivot;
use App\Models\User;
use DB;
use Illuminate\Http\Request;
use Storage;

class EmployeeDocumentPivotController extends Controller
{

    public function show($id)
    {
        $data = EmployeeDocumentPivot::where('id_user', $id)->get();

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

            $employee = User::find($request->id_employee);
            if (!$employee) {
                AuditLogger::log(
                    'Upload Document Failed',
                    "Employee with ID {$request->id_employee} not found.",
                    'error',
                    $request->user()->id ?? null,
                    'upload employee document'
                );

                return response()->json([
                    'success' => false,
                    'message' => "Employee not found"
                ], 404);
            }

            $document = EmployeeDocument::find($request->id_document);
            if (!$document) {
                AuditLogger::log(
                    'Upload Document Failed',
                    "Document type with ID {$request->id_document} not found.",
                    'error',
                    $request->user()->id ?? null,
                    'upload employee document'
                );

                return response()->json([
                    'success' => false,
                    'message' => "Document type not found"
                ], 404);
            }

            if (!$request->document || !preg_match('/^data:([a-zA-Z0-9\/\-\+\.]+);base64,/', $request->document, $matches)) {
                AuditLogger::log(
                    'Upload Document Failed',
                    "Invalid document format uploaded for Employee: {$employee->name}",
                    'error',
                    $request->user()->id ?? null,
                    'upload employee document'
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid document format'
                ], 422);
            }

            $mimeType = $matches[1];
            $extension = explode('/', $mimeType)[1] ?? 'bin';
            $base64Data = preg_replace('/^data:.*;base64,/', '', $request->document);
            $fileData = base64_decode($base64Data);

            if ($fileData === false || base64_encode($fileData) !== $base64Data) {
                AuditLogger::log(
                    'Upload Document Failed',
                    "Invalid or corrupted base64 data for Employee: {$employee->name}",
                    'error',
                    $request->user()->id ?? null
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or corrupted base64 data.'
                ], 422);
            }

            $fileName = uniqid() . '.' . $extension;
            $storagePath = "sop_doc/files/{$fileName}";
            Storage::disk('public')->put($storagePath, $fileData);

            // Hapus file lama jika ada
            $existing = EmployeeDocumentPivot::where('id_user', $employee->id)
                ->where('id_document', $document->id)
                ->first();

            if ($existing) {
                Storage::disk('public')->delete($existing->path);
                $existing->delete();
            }
            EmployeeDocumentPivot::create([
                'id_user' => $employee->id,
                'id_document' => $document->id,
                'path' => $storagePath
            ]);

            DB::commit();

            AuditLogger::log(
                'Employee Document Uploaded',
                "Document '{$document->name}' uploaded for Employee: {$employee->name} (ID: {$employee->id})\nPath: {$storagePath}",
                'success',
                $request->user()->id ?? null,
                'upload employee document'
            );

            return response()->json([
                'success' => true,
                'message' => 'Document uploaded successfully'
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            AuditLogger::log(
                'Upload Document Failed',
                'Exception: ' . $th->getMessage(),
                'error',
                $request->user()->id ?? null,
                'upload employee document'
            );

            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    
}
