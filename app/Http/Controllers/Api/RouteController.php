<?php

namespace App\Http\Controllers\Api;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Route;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RouteController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => Route::all()
        ]);
    }

    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $route = Route::find($id);

            if (!$route) {
                AuditLogger::log(
                    "Failed to update route",
                    "Route with ID $id not found",
                    'error',
                    Auth::id(),
                    'update site route'
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Oops! Something went wrong'
                ], 404);
            }

            $before = [
                'name' => $route->name,
                'status' => $route->status,
                'route' => $route->route,
                'remarks' => $route->remarks ?? ''
            ];

            $route->update([
                'name' => $request->name ?? $route->name,
                'status' => $request->status ?? $route->status,
                'route' => $request->route ?? $route->route,
                'remarks' => $request->remarks ?? $route->remarks
            ]);

            DB::commit();

            $after = [
                'name' => $route->name,
                'status' => $route->status,
                'route' => $request->route,
                'remarks' => $request->remarks
            ];

            $desc = "Data before update:\n";
            foreach ($before as $key => $value) {
                $desc .= ucfirst($key) . ": $value\n";
            }
            $desc .= "\nData after update:\n";
            foreach ($after as $key => $value) {
                $desc .= ucfirst($key) . ": $value\n";
            }

            AuditLogger::log(
                "Route updated by " . (Auth::user()->email ?? 'Unknown'),
                $desc,
                'success',
                Auth::id(),
                'update site route'
            );

            return response()->json([
                'success' => true,
                'message' => 'Route updated successfully'
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            AuditLogger::log(
                "Failed to update route",
                "Error: {$th->getMessage()}",
                'error',
                Auth::id(),
                'update site route'
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

            $route = Route::create([
                'name' => $request->name,
                'id_site' => $request->id_site,
                'route' => $request->route,
                'remarks' => $request->remarks ? $request->remarks : ''
            ]);

            if (!$route) {
                return response()->json([
                    'success' => false,
                    'message' => 'Oops! Something went wrong'
                ], 500);
            }

            DB::commit();

            AuditLogger::log(
                "Route created by " . (Auth::user()->email ?? 'Unknown'),
                "Name: {$route->name}\nSite ID: {$route->id_site}\nRoute: {$route->route}\nRemarks: {$route->remarks}",
                'success',
                Auth::id(),
                'create site route'
            );

            return response()->json([
                'success' => true,
                'message' => 'Route created successfully'
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            AuditLogger::log(
                "Failed to create route",
                "Error: {$th->getMessage()}",
                'error',
                Auth::id(),
                'create site route'
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

            $route = Route::find($id);

            if (!$route) {
                AuditLogger::log(
                    "Failed to delete route",
                    "Route with ID $id not found",
                    'error',
                    Auth::id(),
                    'delete site route'
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Oops! Something went wrong'
                ], 404);
            }

            $routeInfo = "Name: {$route->name}, Site ID: {$route->id_site}";

            $route->delete();

            DB::commit();

            AuditLogger::log(
                "Route deleted by " . (Auth::user()->email ?? 'Unknown'),
                "Deleted Route Info:\n{$routeInfo}",
                'success',
                Auth::id(),
                'delete site route'
            );

            return response()->json([
                'success' => true,
                'message' => 'Route deleted successfully'
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            AuditLogger::log(
                "Failed to delete route",
                "Error: {$th->getMessage()}",
                'error',
                Auth::id(),
                'delete site route'
            );

            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong'
            ], 500);
        }
    }
}
