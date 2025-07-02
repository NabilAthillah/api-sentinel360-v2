<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use DB;
use Hash;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use Storage;

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
                'birth' => $request->birth,
                'briefing_conducted' => $request->briefing_conducted,
                'q1' => $request->q1,
                'a1' => $request->a1,
                'q2' => $request->q2,
                'a2' => $request->a2,
                'q3' => $request->q3,
                'a3' => $request->a3,
                'q4' => $request->q4,
                'a4' => $request->a4,
                'q5' => $request->q5,
                'a5' => $request->a5,
                'q6' => $request->q6,
                'a6' => $request->a6,
                'q7' => $request->q7,
                'a7' => $request->a7,
                'q8' => $request->q8,
                'a8' => $request->a8,
                'q9' => $request->q9,
                'a9' => $request->a9,
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

    public function update(Request $request, $id)
    {
        try {
            //code...
            DB::beginTransaction();

            $employee = Employee::where('id', $id)->first();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found'
                ], 404);
            }

            $user = User::with('employee')->where('id', $employee->id_user)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $employee->update([
                'nric_fin_no' => $request->nric_fin_no,
                'briefing_date' => $request->briefing_date,
                'id_user' => $user->id_user,
                'reporting_to' => $request->reporting_to,
                'birth' => $request->birth,
                'briefing_conducted' => $request->briefing_conducted,
            ]);

            $user->update([
                'name' => $request->name,
                'mobile' => $request->mobile,
                'address' => $request->address,
                'status' => 'active',
                'email' => $request->email,
                'id_role' => $request->id_role
            ]);

            $pathImage = '';

            if ($request->profile) {
                if ($user->profile_image != '') {
                    if (!Storage::delete($user->profile_image)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Oops! Something went wrong'
                        ], 500);
                    }
                }

                $image = $request->profile;
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image));
                $imageName = uniqid() . '.png';
                Storage::disk('public')->put("users/profile/{$imageName}", $imageData);
                $pathImage = "users/profile/{$imageName}";

                $user->update([
                    'profile_image' => $pathImage
                ]);
            }

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
                'q1' => $request->q1,
                'a1' => $request->a1,
                'q2' => $request->q2,
                'a2' => $request->a2,
                'q3' => $request->q3,
                'a3' => $request->a3,
                'q4' => $request->q4,
                'a4' => $request->a4,
                'q5' => $request->q5,
                'a5' => $request->a5,
                'q6' => $request->q6,
                'a6' => $request->a6,
                'q7' => $request->q7,
                'a7' => $request->a7,
                'q8' => $request->q8,
                'a8' => $request->a8,
                'q9' => $request->q9,
                'a9' => $request->a9,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Employee updated successfully',
                'data' => '',
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
