<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceSetting;
use Illuminate\Http\Request;

class AttendanceSettingController extends Controller
{
    public function index()
    {
        try {
            //code...
            $attendanceSettings = AttendanceSetting::first();

            return response()->json([
                'success' => true,
                'data' => $attendanceSettings
            ]);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong'
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            //code...
            $data = AttendanceSetting::first();
            
            $data->update([
                'grace_period' => $request->grace_period,
                'geo_fencing' => $request->geo_fencing,
                'day_shift_start_time' => $request->day_shift_start_time,
                'day_shift_end_time' => $request->day_shift_end_time,
                'night_shift_start_time' => $request->night_shift_start_time,
                'night_shift_end_time' => $request->night_shift_end_time,
                'relief_day_shift_start_time' => $request->relief_day_shift_start_time,
                'relief_day_shift_end_time' => $request->relief_day_shift_end_time,
                'relief_night_shift_start_time' => $request->relief_night_shift_start_time,
                'relief_night_shift_end_time' => $request->relief_night_shift_end_time,
            ]);
            
            $data = AttendanceSetting::first();

            return response()->json([
                'success' => true,
                'message' => 'Attendance Setting updated successfully',
                'data' => $data
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'success' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
