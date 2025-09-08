<?php

namespace App\Http\Controllers\Api;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\IncidentType;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IncidentTypeController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => IncidentType::all()
        ]);
    }

    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $type = IncidentType::find($id);

            if (!$type) {
                AuditLogger::log(
                    "Failed to update incident type",
                    "Type with ID $id not found",
                    'error',
                    Auth::id(),
                    'update incident type'
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Type not found'
                ], 404);
            }

            $oldData = $type->toArray();

            $type->update([
                'name' => $request->name ?? $type->name,
                'status' => $request->status ?? $type->status,
            ]);

            DB::commit();

            $newData = $type->toArray();
            $description = "Updated Incident Type (ID: $id)\n";
            $description .= "Before:\n";
            foreach ($oldData as $key => $value) {
                $description .= ucfirst($key) . ": " . $value . "\n";
            }
            $description .= "After:\n";
            foreach ($newData as $key => $value) {
                $description .= ucfirst($key) . ": " . $value . "\n";
            }

            AuditLogger::log(
                "Incident type updated by " . (Auth::user()->email ?? 'Unknown'),
                $description,
                'success',
                Auth::id(),
                'update incident type'
            );

            return response()->json([
                'success' => true,
                'message' => 'Type updated successfully'
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            AuditLogger::log(
                "Exception while updating incident type",
                "Error: {$th->getMessage()}",
                'error',
                Auth::id(),
                'update incident type'
            );

            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong' . $th->getMessage()
            ], 500);
        }
    }


    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $type = IncidentType::create([
                'name' => $request->name,
            ]);

            if (!$type) {
                AuditLogger::log(
                    "Failed to create incident type",
                    "Attempted with name: {$request->name}",
                    'error',
                    Auth::id(),
                    'update incident type'
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Oops! Something went wrong'
                ], 500);
            }

            DB::commit();

            AuditLogger::log(
                "Incident type created by " . (Auth::user()->email ?? 'Unknown'),
                "Type created with ID: {$type->id}, Name: {$type->name}",
                'success',
                Auth::id(),
                'update incident type'
            );

            return response()->json([
                'success' => true,
                'message' => 'Type created successfully'
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            AuditLogger::log(
                "Exception while creating incident type",
                "Error: {$th->getMessage()}",
                'error',
                Auth::id(),
                'update incident type'
            );

            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong'
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $inc = IncidentType::find($id);

            if (!$inc) {
                return response()->json([
                    'success' => false,
                    'message' => 'Incident Type not found'
                ], 404);
            }

            $deletedData = $inc->toArray();

            if ($inc->document != '') {
                Storage::delete($inc->document);
            }

            $inc->delete();

            DB::commit();

            AuditLogger::log(
                (Auth::user()->email ?? 'Unknown') . " deleted Incident Type: {$deletedData['name']}",
                json_encode($deletedData, JSON_PRETTY_PRINT),
                'success',
                Auth::id(),
                'delete Incident Type'
            );

            return response()->json([
                'success' => true,
                'message' => 'Incident Type deleted successfully'
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            AuditLogger::log(
                "Failed to delete Incident Type ID: {$id}",
                "Error: " . $th->getMessage(),
                'error',
                Auth::id(),
                'delete Incident Type'
            );

            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong'
            ], 500);
        }
    }
}
