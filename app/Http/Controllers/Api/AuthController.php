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
                'account' => ['required', 'string'],
                'password' => ['required']
            ]);

            if (!$request->account || !$request->password) {
                AuditLogger::log(
                    'Login Failed',
                    'Login attempt with missing account or password.',
                    'error',
                    null,
                    'login'
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Account and password are required'
                ], 401);
            }

            $user = User::with('role', 'role.permissions')
                ->where('email', $request->account)
                ->orWhere('mobile', $request->account)
                ->first();

            if (!$user) {
                AuditLogger::log(
                    'Login Failed',
                    "Login attempt with invalid account: {$request->account}",
                    'error',
                    null,
                    'login'
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid account'
                ], 401);
            }

            if (!Hash::check($request->password, $user->password)) {
                AuditLogger::log(
                    'Login Failed',
                    "Invalid password attempt for account: {$request->account}",
                    'error',
                    $user->id,
                    'login'
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid password'
                ], 401);
            }

            if (!Auth::attempt(['email' => $user->email, 'password' => $request->password])) {
                AuditLogger::log(
                    'Login Failed',
                    "Laravel auth attempt failed for account: {$request->account}",
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
                    'error',
                    $user->id,
                    'login'
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Your account is not active. Please contact administrator'
                ], 401);
            }

            $user->last_login = Carbon::now();

            if (!$user->first_login) {
                $user->update([
                    'first_login' => Carbon::now()
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
                'user' => $user,
                'token' => $token
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            AuditLogger::log(
                'Login Failed',
                "User attempted login: {$request->email}",
                'error',
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
                    'error',
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
                'error',
                null,
                'login'
            );

            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong' . $th->getMessage()
            ], 500);
        }
    }

    public function updateProfile(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            //code...
            $request->validate([
                'name' => 'required|string',
                'address' => 'required|string',
                'mobile' => 'required|string',
                'email' => 'required|email',
                'old_password' => 'nullable|string',
                'new_password' => 'nullable|string',
                'profile_image' => 'nullable|string',
            ]);

            $user = User::with('role', 'role.permissions')->where('id', $id)->first();

            if (!$user) {
                DB::rollBack();

                AuditLogger::log(
                    'Update profile failed',
                    "User attempted update profile: {$request->email}",
                    'error',
                    null,
                    'profile'
                );

                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ]);
            }

            $oldData = $user->only(['name', 'email', 'address', 'mobile', 'profile_image']);

            $user->name = $request->name;
            $user->address = $request->address;
            $user->mobile = $request->mobile;
            $user->email = $request->email;

            if ($request->old_password && $request->new_password) {
                if (Hash::check($request->old_password, $user->password)) {
                    $user->password = Hash::make($request->new_password);
                } else {
                    DB::rollBack();

                    AuditLogger::log(
                        'Update profile failed',
                        "User attempted update profile: {$request->email}",
                        'error',
                        null,
                        'profile'
                    );

                    return response()->json([
                        'success' => false,
                        'message' => 'Wrong password'
                    ]);
                }
            }

            if ($request->profile_image) {
                if ($user->profile_image) {
                    Storage::delete($user->profile_image);
                }

                $image = $request->profile_image;
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image));
                $imageName = uniqid() . '.png';
                Storage::disk('public')->put("users/profile/{$imageName}", $imageData);
                $pathImage = "users/profile/{$imageName}";

                $user->profile_image = $pathImage;
            }

            $user->save();

            $newData = $user->only(['name', 'email', 'address', 'mobile', 'profile_image']);

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
            //throw $th;
            DB::rollBack();
            AuditLogger::log(
                'Update profile failed',
                "User attempted update profile: {$request->email}",
                'error',
                null,
                'profile'
            );

            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong' . $th->getMessage()
            ], 500);
        }
    }
}
