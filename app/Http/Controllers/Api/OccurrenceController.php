<?php

namespace App\Http\Controllers\Api;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Occurrence;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Ramsey\Uuid\Uuid;

class OccurrenceController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => Occurrence::with('site', 'category', 'reported_by')->get()
        ], 200);
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $userId = Auth::id();
            $userEmail = Auth::user()->email ?? 'Unknown';

            $data = collect($request->occurrences)->map(function ($item) use ($userId) {
                $dt = Carbon::createFromFormat('Y-m-d\TH:i', $item['occurred_at']);

                return [
                    'id' => Uuid::uuid4(),
                    'id_site' => $item['id_site'],
                    'id_category' => $item['id_category'],
                    'id_user' => $userId,
                    'date' => $dt->toDateString(),
                    'time' => $dt->toTimeString(),
                    'detail' => $item['detail'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            Occurrence::insert($data);
            DB::commit();

            $logDescription = "User: {$userEmail} (ID: {$userId}) created " . count($data) . " occurrence(s):\n";

            foreach ($data as $index => $item) {
                $logDescription .= "\nOccurrence #" . ($index + 1) . "\n";
                foreach ($item as $key => $value) {
                    $logDescription .= ucfirst($key) . ": " . $value . "\n";
                }
            }

            AuditLogger::log(
                "Occurrence created by {$userEmail}",
                $logDescription,
                'success',
                $userId,
                'create occurrence'
            );

            return response()->json([
                'success' => true,
                'message' => 'Occurrence created successfully'
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            AuditLogger::log(
                "Failed to create occurrence",
                "Error: " . $th->getMessage(),
                'error',
                Auth::id(),
                'create occurrence'
            );

            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong. ' . $th->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $userId = Auth::id();
            $userEmail = Auth::user()->email ?? 'Unknown';

            // Validasi data
            $request->validate([
                'id_site' => 'required|exists:sites,id',
                'id_category' => 'required|exists:occurrence_category,id',
                'occurred_at' => 'required|date_format:Y-m-d\TH:i:s',
                'detail' => 'nullable|string',
            ]);

            $occurrence = Occurrence::findOrFail($id);

            // Convert datetime
            $dt = Carbon::createFromFormat('Y-m-d\TH:i:s', $request->occurred_at);

            $occurrence->update([
                'id_site' => $request->id_site,
                'id_category' => $request->id_category,
                'date' => $dt->toDateString(),
                'time' => $dt->toTimeString(),
                'detail' => $request->detail,
                'updated_at' => now(),
            ]);

            DB::commit();

            // Logging
            $logDescription = "User: {$userEmail} (ID: {$userId}) updated occurrence (ID: {$id}):\n";
            foreach ($occurrence->toArray() as $key => $value) {
                $logDescription .= ucfirst($key) . ": " . $value . "\n";
            }

            AuditLogger::log(
                "Occurrence updated by {$userEmail}",
                $logDescription,
                'success',
                $userId,
                'update occurrence'
            );

            return response()->json([
                'success' => true,
                'message' => 'Occurrence updated successfully',
                'data' => $occurrence
            ], 200);

        } catch (\Throwable $th) {
            DB::rollBack();

            AuditLogger::log(
                "Failed to update occurrence",
                "Error: " . $th->getMessage(),
                'error',
                Auth::id(),
                'update occurrence'
            );

            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong. ' . $th->getMessage(),
            ], 500);
        }
    }


    public function destroy($id)
    {
        $data = Occurrence::findOrFail($id);

        $data->delete();

        return response()->json([
            'success' => true
        ]);
    }
}
