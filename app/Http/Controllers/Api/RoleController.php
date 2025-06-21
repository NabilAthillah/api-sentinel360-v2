<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\RolePermission;
use DB;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        try {
            //code...
            $roles = Role::with('permissions')->orderBy('name', 'ASC')->get();

            return response()->json([
                'success' => true,
                'data' => $roles
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            //code...
            DB::beginTransaction();

            $role = Role::create([
                'name' => $request->name
            ]);

            foreach ($request->permissions as $item) {
                RolePermission::create([
                    'id_role' => $role->id,
                    'id_permission' => $item
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Role created successfully',
                'data' => $role
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong'
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            //code...
            DB::beginTransaction();

            $role = Role::where('id', $id)->first();

            if (!$role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role not found'
                ], 401);
            }

            $role->update([
                'name' => $request->name
            ]);

            RolePermission::where('id_role', $role->id)->delete();

            foreach ($request->permissions as $item) {
                RolePermission::create([
                    'id_role' => $role->id,
                    'id_permission' => $item['id']
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Role created successfully',
                'data' => $role
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong'
            ], 500);
        }
    }
}
