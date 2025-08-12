<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\SiteUser;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function getAttendance($id)
    {
        $data = Attendance::where('id_site_employee', $id)->first();

        if ($data) {
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'data' => null,
            ]);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'id_site_employee' => 'required|string',
                'time_in' => 'required|string',
            ]);

            $data = SiteUser::where('id', $request->id_site_employee)->first();

            if (!$data)
                return response()->json([
                    'success' => false,
                    'data' => 'You are not assigned to this shift',
                ]);

            $settings = AttendanceSetting::first();

            $check_in_time = '';

            if ($data->shift === 'day') {
                $check_in_time = $settings->day_shift_start_time;
            } else if ($data->shift === 'night') {
                $check_in_time = $settings->night_shift_start_time;
            } else if ($data->shift === 'relief day') {
                $check_in_time = $settings->relief_day_shift_start_time;
            } else if ($data->shift === 'relief night') {
                $check_in_time = $settings->relief_night_shift_start_time;
            } else {
                return response()->json([
                    'success' => false,
                    'data' => null,
                ]);
            }

            $attendance = Attendance::create([
                'id_site_employee' => $request->id_site_employee,
                'time_in' => $request->time_in,
                'check_in_time' => $check_in_time
            ]);

            return response()->json([
                'success' => true,
                'data' => $attendance,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'data' => null,
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'id_site_employee' => 'required|string',
                'time_out' => 'required|string',
                'is_early' => 'required|boolean',
                'reason' => 'nullable|string'
            ]);

            $data = SiteUser::where('id', $request->id_site_employee)->first();

            if (!$data)
                return response()->json([
                    'success' => false,
                    'data' => 'You are not assigned to this shift',
                ]);

            $settings = AttendanceSetting::first();

            $check_out_time = '';

            if ($data->shift === 'day') {
                $check_out_time = $settings->day_shift_end_time;
            } else if ($data->shift === 'night') {
                $check_out_time = $settings->night_shift_end_time;
            } else if ($data->shift === 'relief day') {
                $check_out_time = $settings->relief_day_shift_end_time;
            } else if ($data->shift === 'relief night') {
                $check_out_time = $settings->relief_night_shift_end_time;
            } else {
                return response()->json([
                    'success' => false,
                    'data' => null,
                ]);
            }

            $attendance = Attendance::where('id', $id)->first();

            $attendance->time_out = $request->time_out;
            $attendance->check_out_time = $check_out_time;
            
            if ($request->is_early) {
                $attendance->reason = $request->reason;
            } else {
                $attendance->reason = '';
            }

            $attendance->save();

            return response()->json([
                'success' => true,
                'data' => $attendance,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'data' => null,
            ]);
        }
    }
}
