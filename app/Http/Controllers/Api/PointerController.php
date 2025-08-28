<?php

namespace App\Http\Controllers\Api;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Pointer;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PointerController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => Pointer::where('id_site', $request->id_site)->get(),
            'id' => $request->id_site
        ]);
    }

    public function store(Request $request)
    {
        try {
            //code...
            $validated = $request->validate([
                'nfc_tag' => 'required|string',
                'id_route' => 'required|string',
                'id_site' => 'required|string',
                'remarks' => 'nullable|string'
            ]);

            $pointer = Pointer::create($validated);

            return response()->json([
                'success' => true,
                'data' => $pointer
            ], 201);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        return response()->json([
            'success' => true,
            'data' => Pointer::with('site', 'route')->where('id_site', $id)->get(),
            'id' => $id
        ]);
    }

        public function update(Request $request, $id)
        {
        try {
            DB::beginTransaction();

            $pointer = Pointer::find($id);
            if (!$pointer) {
                AuditLogger::log(
                    "Failed to update pointer",
                    "Pointer with ID $id not found",
                    'error',
                    Auth::id(),
                    'update pointer'
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Pointer not found'
                ], 404);
            }

            $before = [
                'id_route' => $pointer->id_route,
                'id_site'  => $pointer->id_site,
                'nfc_tag'  => $pointer->nfc_tag,
                'remarks'  => $pointer->remarks ?? '',
                'status'   => $pointer->status ?? '',
            ];

            $validated = $request->validate([
                'id_route' => ['required', 'uuid', 'exists:routes,id'],
                'id_site'  => ['required', 'uuid', 'exists:sites,id'],
                'nfc_tag'  => ['required', 'string', 'max:191'],
                'remarks'  => ['nullable', 'string', 'max:1000'],
            ]);

            $pointer->fill($validated);
            $pointer->save();

            DB::commit();

            $after = [
                'id_route' => $pointer->id_route,
                'id_site'  => $pointer->id_site,
                'nfc_tag'  => $pointer->nfc_tag,
                'remarks'  => $pointer->remarks ?? '',
                'status'   => $pointer->status ?? '',
            ];

            $desc  = "Data before update:\n";
            foreach ($before as $k => $v) $desc .= ucfirst($k) . ": " . (is_scalar($v) ? $v : json_encode($v)) . "\n";
            $desc .= "\nData after update:\n";
            foreach ($after as $k => $v) $desc .= ucfirst($k) . ": " . (is_scalar($v) ? $v : json_encode($v)) . "\n";

            AuditLogger::log(
                "Pointer updated by " . (Auth::user()->email ?? 'Unknown'),
                $desc,
                'success',
                Auth::id(),
                'update pointer'
            );

            return response()->json([
                'success' => true,
                'message' => 'Pointer updated successfully',
                'data'    => $pointer
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            AuditLogger::log(
                "Failed to update pointer",
                "Error: {$th->getMessage()}",
                'error',
                Auth::id(),
                'update pointer'
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

            $pointer = Pointer::find($id);
            if (!$pointer) {
                AuditLogger::log(
                    "Failed to delete pointer",
                    "Pointer with ID $id not found",
                    'error',
                    Auth::id(),
                    'delete pointer'
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Pointer not found'
                ], 404);
            }

            $info = "ID: {$pointer->id}\nRoute ID: {$pointer->id_route}\nSite ID: {$pointer->id_site}\nNFC: {$pointer->nfc_tag}\nStatus: " . ($pointer->status ?? '');

            $pointer->delete();

            DB::commit();

            AuditLogger::log(
                "Pointer deleted by " . (Auth::user()->email ?? 'Unknown'),
                "Deleted Pointer Info:\n{$info}",
                'success',
                Auth::id(),
                'delete pointer'
            );

            return response()->json([
                'success' => true,
                'message' => 'Pointer deleted successfully'
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            AuditLogger::log(
                "Failed to delete pointer",
                "Error: {$th->getMessage()}",
                'error',
                Auth::id(),
                'delete pointer'
            );

            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong' . $th->getMessage()
            ], 500);
        }
    }
}
