<?php

namespace App\Http\Controllers\Api;

use App\Models\Attendance;
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
                'success' => true,
                'data' => null,
            ]);
        }
    }
}
