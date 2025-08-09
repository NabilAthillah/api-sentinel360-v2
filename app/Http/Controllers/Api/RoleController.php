<?php

namespace App\Http\Controllers\Api;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\RolePermission;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            $userEmail = Auth::user()->email ?? 'Unknown';
            $userId = Auth::id();

            $permissionsList = implode(', ', $request->permissions);

            AuditLogger::log(
                "Role '{$role->name}' created by {$userEmail}",
                "Role name: {$role->name}\nPermissions: {$permissionsList}",
                'success',
                $userId,
                'create role'
            );

            return response()->json([
                'success' => true,
                'message' => 'Role created successfully',
                'data' => $role
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            AuditLogger::log(
                "Failed to create role",
                "Error: {$th->getMessage()}",
                'error',
                Auth::id(),
                'create role'
            );

            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong'
            ], 500);
        }
    }


    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $role = Role::find($id);

            if (!$role) {
                AuditLogger::log(
                    "Failed to update role",
                    "Role with ID $id not found",
                    'error',
                    Auth::id(),
                    'update role'
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Role not found'
                ], 404);
            }

            $oldName = $role->name;
            $oldPermissions = RolePermission::where('id_role', $role->id)->pluck('id_permission')->toArray();

            $role->update([
                'name' => $request->name
            ]);

            RolePermission::where('id_role', $role->id)->delete();

            $newPermissions = [];
            foreach ($request->permissions as $item) {
                $permissionId = is_array($item) ? $item['id'] : $item;
                RolePermission::create([
                    'id_role' => $role->id,
                    'id_permission' => $permissionId
                ]);
                $newPermissions[] = $permissionId;
            }

            DB::commit();

            $userEmail = Auth::user()->email ?? 'Unknown';
            $userId = Auth::id();

            $logDescription = "Data before update:\n";
            $logDescription .= "Name: {$oldName}\n";
            $logDescription .= "Permissions: " . implode(', ', $oldPermissions) . "\n\n";

            $logDescription .= "Data after update:\n";
            $logDescription .= "Name: {$request->name}\n";
            $logDescription .= "Permissions: " . implode(', ', $newPermissions) . "\n";

            AuditLogger::log(
                "Role '{$oldName}' updated by {$userEmail}",
                $logDescription,
                'success',
                $userId,
                'update role'
            );

            return response()->json([
                'success' => true,
                'message' => 'Role updated successfully',
                'data' => $role
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            AuditLogger::log(
                "Failed to update role",
                "Error: {$th->getMessage()}",
                'error',
                Auth::id(),
                'update role'
            );

            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong'
            ], 500);
        }
    }
}
