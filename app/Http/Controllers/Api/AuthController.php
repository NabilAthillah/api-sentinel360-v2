<?php

namespace App\Http\Controllers\Api;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\User;
use Auth;
use Carbon\Carbon;
use DB;
use Hash;
use Illuminate\Http\Request;
use Storage;

class AuthController extends Controller
{

    public function login(Request $request)
    {
        try {
            DB::beginTransaction();

            $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required']
            ]);

            if (!$request->email || !$request->password) {
                AuditLogger::log(
                    'Login Failed',
                    'Login attempt with missing email or password.',
                    'error',
                    null,
                    'login'
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Email and password are required'
                ], 401);
            }

            $user = User::with('role', 'role.permissions', 'employee')
                ->where('email', $request->email)
                ->first();

            if (!$user) {
                AuditLogger::log(
                    'Login Failed',
                    "Login attempt with invalid email: {$request->email}",
                    'error',
                    null,
                    'login'
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email'
                ], 401);
            }

            if (!Hash::check($request->password, $user->password)) {
                AuditLogger::log(
                    'Login Failed',
                    "Invalid password attempt for email: {$request->email}",
                    'error',
                    $user->id,
                    'login'
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid password'
                ], 401);
            }

            if (!Auth::attempt($request->only('email', 'password'))) {
                AuditLogger::log(
                    'Login Failed',
                    "Laravel auth attempt failed for email: {$request->email}",
                    'error',
                    $user->id,
                    'login'
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Oops! Something went wrong'
                ], 401);
            }

            if ($user->status != 'active') {
                AuditLogger::log(
                    'Login Blocked',
                    "Inactive user attempted login: {$user->email}",
                    'warning',
                    $user->id,
                    'login'
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Your account is not active. Please contact administrator'
                ], 401);
            }

            if (!$user->last_login) {
                $user->update([
                    'last_login' => Carbon::now()
                ]);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            AuditLogger::log(
                'Login Successful',
                "User {$user->email} logged in successfully.",
                'success',
                $user->id,
                'login'
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => $user,
                    'token' => $token
                ]
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            AuditLogger::log(
                'Login Failed',
                "User attempted login: {$request->email}",
                'warning',
                null,
                'login'
            );

            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong' . $th->getMessage()
            ], 500);
        }
    }

    public function loginUser(Request $request)
    {
        try {
            DB::beginTransaction();

            $request->validate([
                'phone' => ['required', 'string'],
                'password' => ['required']
            ]);

            if (!$request->phone || !$request->password) {
                AuditLogger::log(
                    'Login Failed',
                    'Login attempt with missing phone or password.',
                    'error',
                    null,
                    'login'
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Phone and password are required'
                ], 401);
            }


            $user = User::with('role', 'role.permissions', 'employee')
                ->where('mobile', $request->phone)
                ->first();

            if (!$user) {
                AuditLogger::log(
                    'Login Failed',
                    "Login attempt with invalid phone: {$request->phone}",
                    'error',
                    null,
                    'login'
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid phone'
                ], 401);
            }

            if (!Hash::check($request->password, $user->password)) {
                AuditLogger::log(
                    'Login Failed',
                    "Invalid password attempt for phone: {$request->phone}",
                    'error',
                    $user->id,
                    'login'
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid password'
                ], 401);
            }

            if ($user->status != 'active') {
                AuditLogger::log(
                    'Login Blocked',
                    "Inactive user attempted login: {$user->email}",
                    'warning',
                    $user->id,
                    'login'
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Your account is not active. Please contact administrator'
                ], 403);
            }

            Auth::login($user);

            if (!$user->last_login) {
                $user->update([
                    'last_login' => Carbon::now()
                ]);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            AuditLogger::log(
                'Login Successful',
                "User {$user->email} logged in successfully.",
                'success',
                $user->id,
                'login'
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => $user,
                    'token' => $token
                ]
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            AuditLogger::log(
                'Login Failed',
                "User attempted login: {$request->phone}",
                'warning',
                null,
                'login'
            );

            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong' . $th->getMessage()
            ], 500);
        }
    }

    public function updateProfile(Request $request)
    {
        try {
            DB::beginTransaction();

            $user = User::with('role', 'role.permissions', 'employee')->where('id', $request->id)->first();

            if (!$user) {
                AuditLogger::log(
                    'Update Profile Failed',
                    "User with ID {$request->id} not found.",
                    'error',
                    $request->id,
                    'update profile'
                );

                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 401);
            }

            $oldData = $user->only(['name', 'email', 'address', 'mobile', 'profile_image']);

            $pathImage = '';

            if ($request->profile) {
                if ($user->profile_image) {
                    Storage::delete($user->profile_image);
                }

                $image = $request->profile;
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image));
                $imageName = uniqid() . '.png';
                Storage::disk('public')->put("users/profile/{$imageName}", $imageData);
                $pathImage = "users/profile/{$imageName}";

                $user->update([
                    'profile_image' => $pathImage,
                ]);
            }

            if ($user->email != $request->email) {
                $exists = User::where('email', $request->email)->first();

                if ($exists) {
                    AuditLogger::log(
                        'Update Profile Failed',
                        "Email {$request->email} is already used by another account.",
                        'error',
                        $user->id,
                        'update profile'
                    );

                    return response()->json([
                        'success' => false,
                        'message' => 'Employee with this email already exists'
                    ], 401);
                }
            }

            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'address' => $request->address,
                'mobile' => $request->mobile,
            ]);

            if ($request->old_password) {
                $valid = Hash::check($request->old_password, $user->password);

                if (!$valid) {
                    AuditLogger::log(
                        'Update Password Failed',
                        "Invalid old password provided by user {$user->email}.",
                        'error',
                        $user->id,
                        'update profile'
                    );

                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid old password'
                    ], 401);
                }

                $user->update([
                    'password' => Hash::make($request->new_password)
                ]);
            }

            $newData = $user->only(['name', 'email', 'address', 'mobile', 'profile_image']);

            // Build log description
            $description = "User {$user->email} updated their profile.\n\n";
            $description .= "Data before update:\n";
            foreach ($oldData as $key => $value) {
                $description .= ucfirst($key) . ": {$value}\n";
            }

            $description .= "\nData after update:\n";
            foreach ($newData as $key => $value) {
                $description .= ucfirst($key) . ": {$value}\n";
            }

            AuditLogger::log(
                'Profile Updated',
                $description,
                'success',
                $user->id,
                'update profile'
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'user' => $user,
                ]
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            AuditLogger::log(
                'Update Profile Failed',
                'Exception: ' . $th->getMessage(),
                'error',
                $request->id,
                'update profile'
            );

            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong'
            ], 500);
        }
    }
}
