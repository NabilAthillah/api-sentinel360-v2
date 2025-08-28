<?php

namespace App\Http\Controllers\Api;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use DB;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Ramsey\Uuid\Uuid;
use Storage;

class EmployeeController extends Controller
{
    public function index()
    {
        try {
            $employees = User::with('role')->get();

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
            DB::beginTransaction();

            $exists = User::where('email', $request->email)->first();

            if ($exists) {
                AuditLogger::log(
                    "Attempt to add duplicate employee",
                    "Employee with email {$request->email} already exists",
                    'error',
                    $request->user()->id ?? null,
                    'create employee'
                );

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
                'id_role' => $request->id_role,
                'nric_fin_no' => $request->nric_fin_no,
                'briefing_date' => $request->briefing_date,
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

            if (!$user) {
                AuditLogger::log(
                    "Failed to create user record",
                    "Error occurred while creating user: {$request->email}",
                    'error',
                    $request->user()->id ?? null,
                    'create employee'
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Oops! Something went wrong'
                ], 500);
            }

            DB::commit();

            AuditLogger::log(
                "{$request->user()->email} has added a new employee",
                "New Employee Created:\n" .
                "User Info:\n" .
                "- Name: {$request->name}\n" .
                "- Email: {$request->email}\n" .
                "- Mobile: {$request->mobile}\n" .
                "- Address: {$request->address}\n" .
                "- Role ID: {$request->id_role}\n" .
                "- Status: inactive\n\n" .
                "Employee Info:\n" .
                "- NRIC/FIN No: {$request->nric_fin_no}\n" .
                "- Birth Date: {$request->birth}\n" .
                "- Briefing Date: {$request->briefing_date}\n" .
                "- Briefing Conducted: {$request->briefing_conducted}\n" .
                "- Date Joined: {$request->date_joined}\n" .
                "- Reporting To: {$request->reporting_to}\n" .
                "- Q1: {$request->q1} | A1: {$request->a1}\n" .
                "- Q2: {$request->q2} | A2: {$request->a2}\n" .
                "- Q3: {$request->q3} | A3: {$request->a3}\n" .
                "- Q4: {$request->q4} | A4: {$request->a4}\n" .
                "- Q5: {$request->q5} | A5: {$request->a5}\n" .
                "- Q6: {$request->q6} | A6: {$request->a6}\n" .
                "- Q7: {$request->q7} | A7: {$request->a7}\n" .
                "- Q8: {$request->q8} | A8: {$request->a8}\n" .
                "- Q9: {$request->q9} | A9: {$request->a9}",
                'success',
                $request->user()->id ?? null,
                'create employee'
            );


            return response()->json([
                'success' => true,
                'message' => 'Employee created successfully',
                'data' => [
                    'user' => $user,
                ]
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            AuditLogger::log(
                "Employee creation failed",
                "Error: {$th->getMessage()}",
                'error',
                $request->user()->id ?? null,
                'create employee'
            );

            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong. ' . $th->getMessage(),
            ], 500);
        }
    }
    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $employee = User::where('id', $id)->first();
            if (!$employee) {
                AuditLogger::log(
                    "Failed to update employee",
                    "Employee with ID $id not found",
                    'error',
                    $request->user()->id ?? null,
                    'update employee'
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found'
                ], 404);
            }

            $existingUser = User::where('email', $request->email)->where('id', '!=', $employee->id)->first();
            if ($existingUser) {
                AuditLogger::log(
                    "Email conflict on employee update",
                    "Attempted to update to existing email: {$request->email}",
                    'error',
                    $request->user()->id ?? null,
                    'update employee'
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Employee with this email already exists'
                ], 409);
            }

            $originalUser = $employee->replicate();

            $employee->update([
                'name' => $request->name,
                'nric_fin_no' => $request->nric_fin_no,
                'mobile' => $request->mobile,
                'email' => $request->email,
                'address' => $request->address,
                'id_role' => $request->id_role,
                'briefing_date' => $request->briefing_date,
                'date_joined' => $request->date_joined,
                'briefing_conducted' => $request->briefing_conducted,
            ]);

            if ($request->profile) {
                if ($employee->profile_image && Storage::disk('public')->exists($employee->profile_image)) {
                    Storage::disk('public')->delete($employee->profile_image);
                }

                $image = $request->profile;

                if (preg_match('/^data:image\/(\w+);base64,/', $image, $type)) {
                    $image = substr($image, strpos($image, ',') + 1);
                    $type = strtolower($type[1]);

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

                    $employee->update(['profile_image' => $pathImage]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid base64 image format'
                    ], 400);
                }
            }

            DB::commit();

            $description = "{$request->user()->email} updated employee {$originalUser->email}\n\n";
            $description .= "Data before updated:\n";
            $description .= "Name: {$originalUser->name}\n";
            $description .= "Email: {$originalUser->email}\n";
            $description .= "Mobile: {$originalUser->mobile}\n";
            $description .= "Address: {$originalUser->address}\n";
            $description .= "Role ID: {$originalUser->id_role}\n\n";
            $description .= "NRIC/FIN No: {$request->nric_fin_no}\n";
            $description .= "Briefing Date: {$request->briefing_date}\n";
            $description .= "Briefing Conducted: {$request->briefing_conducted}\n";
            $description .= "Date Joined: {$request->date_joined}\n";

            $description .= "Data after updated:\n";
            $description .= "Name: {$request->name}\n";
            $description .= "Email: {$request->email}\n";
            $description .= "Mobile: {$request->mobile}\n";
            $description .= "Address: {$request->address}\n";
            $description .= "Role ID: {$request->id_role}\n";
            $description .= "NRIC/FIN No: {$request->nric_fin_no}\n";
            $description .= "Briefing Date: {$request->briefing_date}\n";
            $description .= "Briefing Conducted: {$request->briefing_conducted}\n";
            $description .= "Date Joined: {$request->date_joined}\n";

            AuditLogger::log(
                "{$request->user()->email} updated employee {$request->name}",
                $description,
                'success',
                $request->user()->id ?? null,
                'update employee'
            );

            return response()->json([
                'success' => true,
                'message' => 'Employee updated successfully'
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            AuditLogger::log(
                "Failed to update employee",
                "Error: {$th->getMessage()}",
                'error',
                $request->user()->id ?? null,
                'update employee'
            );

            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong',
                'error' => $th->getMessage()
            ], 500);
        }
    }


    public function updateStatus(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $employee = User::find($id);

            if (!$employee) {
                AuditLogger::log(
                    "Failed to update employee status",
                    "Employee with ID $id not found",
                    'error',
                    $request->user()->id ?? null,
                    'update employee status'
                );

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

            $oldStatus = $employee->status;

            if ($request->status === 'rejected') {
                $employeeName = $employee->name ?? 'Unknown';

                $employee->delete();

                DB::commit();

                AuditLogger::log(
                    "Employee Rejected and Deleted",
                    "{$request->user()->email} rejected and deleted employee: {$employeeName}",
                    'error',
                    $request->user()->id ?? null,
                    'update employee status'
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Employee rejected and deleted successfully'
                ], 200);
            }

            if ($request->status === 'accepted') {
                $employee->update(['status' => 'active']);
            }

            DB::commit();

            AuditLogger::log(
                "Employee Status Updated",
                "{$request->user()->email} updated employee ID {$employee->id} status from {$oldStatus} to {$request->status}",
                'success',
                $request->user()->id ?? null,
                'update employee status'
            );

            return response()->json([
                'success' => true,
                'message' => 'Employee status updated successfully',
                'data' => $employee
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            AuditLogger::log(
                "Failed to update employee status",
                "Error: {$th->getMessage()}",
                'error',
                $request->user()->id ?? null,
                'update employee status'
            );

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

            $employee = User::find($id);

            if (!$employee) {
                AuditLogger::log(
                    "Failed to delete employee",
                    "Employee with ID $id not found",
                    'error',
                    $request->user()->id ?? null,
                    'delete employee'
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found'
                ], 404);
            }

            $employeeName = $employee->name;
            $employeeEmail = $employee->email;

            $employee->delete();

            DB::commit();

            AuditLogger::log(
                "Employee Deleted",
                "{$request->user()->email} deleted employee: {$employeeName} ({$employeeEmail})",
                'error',
                $request->user()->id ?? null,
                'delete employee'
            );

            return response()->json([
                'success' => true,
                'message' => 'Employee deleted successfully',
                'data' => [
                    'user' => $employee,
                ]
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            AuditLogger::log(
                "Failed to delete employee",
                "Error: {$th->getMessage()}",
                'error',
                $request->user()->id ?? null,
                'delete employee'
            );

            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function updateProfile(Request $request, $id)
    {
        if ((int) $request->user()->id !== (int) $id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($id)],
            'birth' => ['nullable', 'date_format:Y-m-d'],
            'nric_fin_no' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::with('employee', 'role')->findOrFail($id);

        DB::transaction(function () use ($user, $validated) {
            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'mobile' => $validated['mobile'] ?? null,
            ]);

            $user->employee()->updateOrCreate(
                ['id_user' => $user->id],
                [
                    'birth' => $validated['birth'] ?? null,
                    'nric_fin_no' => $validated['nric_fin_no'] ?? null,
                ]
            );
        });

        $user->load(['employee', 'role']);

        return response()->json([
            'success' => true,
            'user' => $user,
        ]);
    }
}
