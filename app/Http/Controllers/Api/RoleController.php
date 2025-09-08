<?php

namespace App\Http\Controllers\Api;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\RolePermission;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Storage;

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
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        try {
            DB::beginTransaction();

            $role = Role::create([
                'name' => $request->name
            ]);

            foreach ($request->permissions as $item) {
                $permissionId = is_array($item) ? $item['id'] : $item;
                RolePermission::create([
                    'id_role' => $role->id,
                    'id_permission' => $permissionId
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
            ], 201);
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
                'message' => 'Oops! Something went wrong',
                'error' => $th->getMessage()
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

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $role = Role::find($id);

            if (!$role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role not found'
                ], 404);
            }

            $deletedData = $role->toArray();

            DB::table('role_permission')->where('id_role', $id)->delete();

            if (!empty($role->document)) {
                Storage::delete($role->document);
            }

            $role->delete();

            DB::commit();

            AuditLogger::log(
                (Auth::user()->email ?? 'Unknown') . " deleted Role: {$deletedData['name']}",
                json_encode($deletedData, JSON_PRETTY_PRINT),
                'success',
                Auth::id(),
                'delete Role'
            );

            return response()->json([
                'success' => true,
                'message' => 'Role deleted successfully'
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            AuditLogger::log(
                "Failed to delete Role ID: {$id}",
                "Error: " . $th->getMessage(),
                'error',
                Auth::id(),
                'delete Role'
            );

            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong'
            ], 500);
        }
    }
}
