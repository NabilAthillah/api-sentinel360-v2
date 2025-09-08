<?php

namespace App\Http\Controllers\Api;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\EmployeeDocument;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            DB::beginTransaction();

            $document = EmployeeDocument::where('id', $id)->first();

            if (!$document) {
                AuditLogger::log(
                    'Update Document Failed',
                    "Document with ID {$id} not found.",
                    'error',
                    $request->user()->id ?? null,
                    'update employee document'
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Oops! Something went wrong'
                ], 404);
            }

            $oldData = $document->only(['name', 'status']);

            $document->update([
                'name' => $request->name ?? $document->name,
                'status' => $request->status ?? $document->status,
            ]);

            $newData = $document->only(['name', 'status']);

            $description = "Updated employee document (ID: {$document->id}).\n\n";
            $description .= "Data before update:\n";
            foreach ($oldData as $key => $value) {
                $description .= ucfirst($key) . ": {$value}\n";
            }

            $description .= "\nData after update:\n";
            foreach ($newData as $key => $value) {
                $description .= ucfirst($key) . ": {$value}\n";
            }

            AuditLogger::log(
                'Employee Document Updated',
                $description,
                'success',
                $request->user()->id ?? null,
                'update employee document'
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Document updated successfully'
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            AuditLogger::log(
                'Update Document Failed',
                'Exception: ' . $th->getMessage(),
                'error',
                $request->user()->id ?? null,
                'update employee document'
            );

            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong',
            ], 500);
        }
    }
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $document = EmployeeDocument::create([
                'name' => $request->name,
            ]);

            if (!$document) {
                AuditLogger::log(
                    'Create Document Failed',
                    "Failed to create document with name {$request->name}.",
                    'error',
                    $request->user()->id ?? null,
                    'create employee document'
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Oops! Something went wrong'
                ], 500);
            }

            AuditLogger::log(
                'Document Created',
                "Created employee document with name: {$document->name} (ID: {$document->id})",
                'success',
                $request->user()->id ?? null,
                'create employee document'
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Document created successfully'
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            AuditLogger::log(
                'Create Document Failed',
                'Exception: ' . $th->getMessage(),
                'error',
                $request->user()->id ?? null,
                'create employee document'
            );

            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong' . $th->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $emp = EmployeeDocument::find($id);

            if (!$emp) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee Document not found'
                ], 404);
            }

            $deletedData = $emp->toArray();

            if ($emp->document != '') {
                Storage::delete($emp->document);
            }

            $emp->delete();

            DB::commit();

            AuditLogger::log(
                (Auth::user()->email ?? 'Unknown') . " deleted Employee Document: {$deletedData['name']}",
                json_encode($deletedData, JSON_PRETTY_PRINT),
                'success',
                Auth::id(),
                'delete Employee Document'
            );

            return response()->json([
                'success' => true,
                'message' => 'Employee Document deleted successfully'
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            AuditLogger::log(
                "Failed to delete Employee Document ID: {$id}",
                "Error: " . $th->getMessage(),
                'error',
                Auth::id(),
                'delete Employee Document'
            );

            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong'
            ], 500);
        }
    }
}
