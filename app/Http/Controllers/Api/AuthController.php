<?php

namespace App\Http\Controllers\Api;

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
                return response()->json([
                    'success' => false,
                    'message' => 'Email and password are required'
                ], 401);
            }

            $user = User::with('role', 'role.permissions', 'employee')->where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid emai'
                ], 401);
            }

            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid password'
                ], 401);
            }

            if (!Auth::attempt($request->only('email', 'password'))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Oops! Something went wrong'
                ], 401);
            }

            if ($user->status != 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is not active. Please contact administrator'
                ], 401);
            }

            if (!$user->las_login) {
                $user->update([
                    'last_login' => Carbon::now()
                ]);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Login successfull',
                'data' => [
                    'user' => $user,
                    'token' => $token
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

    public function updateProfile(Request $request)
    {
        try {
            DB::beginTransaction();

            $user = User::with('role', 'role.permissions', 'employee')->where('id', $request->id)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 401);
            }

            $pathImage = '';

            if ($request->profile) {
                if ($user->profile) {
                    Storage::delete($user->profile);
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
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid old password'
                    ], 401);
                }

                $user->update([
                    'password' => Hash::make($request->new_password)
                ]);
            }

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
            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong'
            ], 500);
        }
    }
}
