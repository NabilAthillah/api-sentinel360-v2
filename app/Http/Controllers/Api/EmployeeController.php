<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use DB;
use Hash;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;

class EmployeeController extends Controller
{
    public function index()
    {
        try {
            //code...
            $employees = Employee::with('reporting', 'user', 'user.role')->get();

            return response()->json([
                'success' => true,
                'data' => $employees
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
        // if (!$request->user()) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Forbidden'
        //     ], 403);
        // }

        // $user = $request->user()->load('role', 'role.permissions');

        // if($user)
        try {
            //code...
            DB::beginTransaction();
            $exists = User::where('email', $request->email)->first();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee with this email already exists'
                ], 401);
            }

            $user_id = Uuid::uuid4();

            $user = User::create([
                'id' => $user_id,
                'name' => $request->name,
                'mobile' => $request->mobile,
                'address' => $request->address,
                'status' => 'active',
                'email' => $request->email,
                'password' => Hash::make($request->name),
                'id_role' => $request->id_role
            ]);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Oops! Something went wrong'
                ], 500);
            }

            Employee::create([
                'nric_fin_no' => $request->nric_fin_no,
                'briefing_date' => $request->briefing_date,
                'id_user' => $user_id,
                'reporting_to' => $request->reporting_to,
                'shift' => $request->shift
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Employee created successfully',
                'data' => [
                    'user' => $user,
                ]
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong'
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            //code...
            DB::beginTransaction();

            $employee = Employee::where('id', $id)->first();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found'
                ], 401);
            }

            $user = User::where('id', $employee->id_user)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found'
                ], 401);
            }

            $employee->delete();
            $user->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Employee deleted successfully',
                'data' => [
                    'user' => $user,
                ]
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
