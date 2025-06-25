<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Route;
use DB;
use Illuminate\Http\Request;

class RouteController extends Controller
{
    public function update(Request $request, $id)
    {
        try {
            //code...
            DB::beginTransaction();

            $route = Route::where('id', $id)->first();

            if (!$route) {
                return response()->json([
                    'success' => false,
                    'message' => 'Oops! Something went wrong'
                ], 404);
            }

            $route->update([
                'name' => $request->name ?? $route->name,
                'status' => $request->status ?? $route->status,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Route updated successfully'
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

            $route = Route::create([
                'name' => $request->name,
                'id_site' => $request->id_site
            ]);

            if (!$route) {
                return response()->json([
                    'success' => false,
                    'message' => 'Oops! Somtehing went wrong'
                ], 500);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Route created successfully'
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'success' => false,
                'message' => 'Oops! Somtehing went wrong'
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            //code...
            DB::beginTransaction();

            $route = Route::where('id', $id)->first();

            if (!$route) {
                return response()->json([
                    'success' => false,
                    'message' => 'Oops! Something went wrong'
                ], 404);
            }

            $route->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Route deleted successfully'
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
