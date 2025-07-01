<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IncidentType;
use DB;
use Illuminate\Http\Request;

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
            //code...
            DB::beginTransaction();

            $type = IncidentType::where('id', $id)->first();

            if (!$type) {
                return response()->json([
                    'success' => false,
                    'message' => 'Oops! Something went wrong'
                ], 404);
            }

            $type->update([
                'name' => $request->name ?? $type->name,
                'status' => $request->status ?? $type->status,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Type updated successfully'
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

            $type = IncidentType::create([
                'name' => $request->name,
            ]);

            if (!$type) {
                return response()->json([
                    'success' => false,
                    'message' => 'Oops! Somtehing went wrong'
                ], 500);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Type created successfully'
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
