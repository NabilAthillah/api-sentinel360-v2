<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use DB;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Ramsey\Uuid\Uuid;
use Storage;

class EmployeeController extends Controller
{
    public function index()
    {
        try {
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
                'status' => 'inactive',
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
                'date_joined' => $request->date_joined,
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
                'status' => 'pending'
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
                'message' => 'Oops! Something went wrong' . $th->getMessage(),
            ], 500);
        }
    }
    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $employee = Employee::where('id', $id)->first();
            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found'
                ], 404);
            }

            $user = User::where('id', $employee->id_user)->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Check email conflict
            $existingUser = User::where('email', $request->email)->where('id', '!=', $user->id)->first();
            if ($existingUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee with this email already exists'
                ], 409);
            }

            // Update user basic info
            $user->update([
                'name' => $request->name,
                'mobile' => $request->mobile,
                'address' => $request->address,
                'status' => 'inactive',
                'email' => $request->email,
                'id_role' => $request->id_role
            ]);

            // ✅ Handle profile image base64
            if ($request->profile) {
                // Hapus gambar lama
                if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
                    Storage::disk('public')->delete($user->profile_image);
                }

                $image = $request->profile;

                if (preg_match('/^data:image\/(\w+);base64,/', $image, $type)) {
                    $image = substr($image, strpos($image, ',') + 1); // Buang header
                    $type = strtolower($type[1]); // jpg, png, etc.

                    // Validasi ekstensi
                    if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif'])) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Invalid image type'
                        ], 400);
                    }

                    $image = str_replace(' ', '+', $image);
                    $imageData = base64_decode($image);

                    if ($imageData === false) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Base64 decoding failed'
                        ], 400);
                    }

                    $imageName = uniqid() . '.' . $type;
                    $pathImage = "users/profile/{$imageName}";
                    Storage::disk('public')->put($pathImage, $imageData);

                    $user->update(['profile_image' => $pathImage]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid base64 image format'
                    ], 400);
                }
            }

            // Update employee fields
            $employee->update([
                'nric_fin_no' => $request->nric_fin_no,
                'briefing_date' => $request->briefing_date,
                'reporting_to' => $request->reporting_to,
                'birth' => $request->birth,
                'briefing_conducted' => $request->briefing_conducted,
                'date_joined' => $request->date_joined,
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
                'message' => 'Employee updated successfully'
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong',
                'error' => $th->getMessage()
            ], 500);
        }
    }

   public function updateStatus(Request $request, $id)
{
    try {
        $employee = Employee::with('user')->find($id);

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not found'
            ], 404);
        }

        $validStatuses = ['pending', 'accepted', 'rejected'];
        if (!in_array($request->status, $validStatuses)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status value'
            ], 400);
        }

        $employee->update(['status' => $request->status]);

        if ($request->status === 'accepted' && $employee->user) {
            $employee->user->update(['status' => 'active']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Employee status updated successfully',
            'data' => $employee
        ], 200);
    } catch (\Throwable $th) {
        return response()->json([
            'success' => false,
            'message' => 'Oops! Something went wrong',
            'error' => $th->getMessage()
        ], 500);
    }
}

    public function destroy(Request $request, $id)
    {
        try {
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
