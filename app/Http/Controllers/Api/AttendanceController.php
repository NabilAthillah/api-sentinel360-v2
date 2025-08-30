<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\SiteUser;
use Carbon\Carbon;
use Dotenv\Exception\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    private function toDbShift(string $shift): string
    {
        return match ($shift) {
            'relief day' => 'relief day',
            'relief night' => 'relief night',
            default => $shift,
        };
    }

    private function fromDbShift(string $shift): string
    {
        return match ($shift) {
            'relief day' => 'relief day',
            'relief night' => 'relief night',
            default => $shift,
        };
    }

    private function formatRow(Attendance $row, string $tz): array
    {
        return [
            'id' => $row->id,
            'site_id' => $row->site_id,
            'user_id' => $row->user_id,
            'date' => Carbon::parse($row->date, $tz)->toDateString(),
            'shift' => $this->fromDbShift($row->shift),
            'time_in' => $row->time_in ? Carbon::parse($row->time_in)->toIso8601String() : null,
            'time_out' => $row->time_out ? Carbon::parse($row->time_out)->toIso8601String() : null,
        ];
    }

    public function index(Request $request)
    {
        try {
            $data = $request->validate([
                'site_id' => 'required|string',
                'user_id' => 'required|string',
                'shift' => 'required',
                'date' => 'required|date_format:Y-m-d',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $tz = 'Asia/Singapore';
        $date = Carbon::createFromFormat('Y-m-d', $data['date'], $tz)->startOfDay();

        $dbShift = $this->toDbShift($data['shift']);

        $row = Attendance::query()
            ->where('id_site', $data['site_id'])
            ->where('id_user', $data['user_id'])
            ->whereDate('date', $date->toDateString())
            ->where('shift', $dbShift)
            ->orderByDesc('created_at')
            ->first();

        if (!$row) {
            return response()->json(['success' => true, 'data' => null]);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatRow($row, $tz),
        ]);
    }

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

    public function showBySiteUserShift(Request $req)
    {
        $data = $req->validate([
            'site_id' => 'required|uuid',
            'user_id' => 'required',
            'shift' => ['required', Rule::in(['day', 'night', 'relief day', 'relief-night'])],
            'date' => 'required|date_format:Y-m-d',
        ]);

        $att = Attendance::where('id_site', $data['site_id'])
            ->where('id_user', $data['user_id'])
            ->where('shift', $data['shift'])
            ->where('date', $data['date'])
            ->first();

        return response()->json([
            'success' => true,
            'data' => $att,
        ]);
    }

    public function checkIn(Request $req)
    {
        $data = $req->validate([
            'site_id' => 'required|uuid',
            'user_id' => 'required',
            'shift' => ['required', Rule::in(['day', 'night', 'relief day', 'relief night'])],
            'date' => 'required|date_format:Y-m-d',
        ]);

        $settings = AttendanceSetting::firstOrFail();
        $tz = 'Asia/Singapore';
        $now = Carbon::now($tz);

        [$start, $end] = $this->shiftStartEnd($data['shift'], $data['date'], $settings, $tz);
        $startPlusGrace = $start->copy()->addMinutes((int) $settings->grace_period);

        if ($now->lt($start)) {
            return response()->json(['success' => false, 'message' => "Check-in window hasn't started yet"], 422);
        }
        if ($now->gte($startPlusGrace)) {
            return response()->json(['success' => false, 'message' => "You are late for check-in"], 422);
        }

        $att = Attendance::firstOrCreate(
            [
                'id_site' => $data['site_id'],
                'id_user' => $data['user_id'],
                'shift' => $data['shift'],
                'date' => $data['date'],
            ]
        );

        if ($att->time_in) {
            return response()->json(['success' => false, 'message' => 'Already checked in'], 409);
        }

        $att->time_in = $now->format('H:i:s');
        $att->save();

        return response()->json(['success' => true, 'message' => 'Checked in', 'data' => $att]);
    }

    public function checkOut(Request $req)
    {
        $data = $req->validate([
            'site_id' => 'required|uuid',
            'user_id' => 'required',
            'shift' => ['required', Rule::in(['day', 'night', 'relief day', 'relief night'])],
            'date' => 'required|date_format:Y-m-d',
            'reason' => 'nullable|string'
        ]);

        $settings = AttendanceSetting::firstOrFail();
        $tz = 'Asia/Singapore';
        $now = Carbon::now($tz);

        $att = Attendance::where('id_site', $data['site_id'])
            ->where('id_user', $data['user_id'])
            ->where('shift', $data['shift'])
            ->where('date', $data['date'])
            ->first();

        if (!$att || !$att->time_in) {
            return response()->json(['success' => false, 'message' => 'You must check in first'], 422);
        }
        if ($att->time_out) {
            return response()->json(['success' => false, 'message' => 'Already checked out'], 409);
        }

        [$start, $end] = $this->shiftStartEnd($data['shift'], $data['date'], $settings, $tz);
        $endPlusGrace = $end->copy()->addMinutes((int) $settings->grace_period);

        if ($now->lt($end) && !$req->reason) {
            return response()->json(['success' => false, 'message' => "Check-out window hasn't started yet"], 422);
        }
        if ($now->gt($endPlusGrace)) {
            return response()->json(['success' => false, 'message' => "You are late for check-out"], 422);
        }

        $att->time_out = $now->format('H:i:s');

        if ($req->reason) {
            $att->reason = $req->reason;
        }

        $att->save();

        return response()->json(['success' => true, 'message' => 'Checked out', 'data' => $att]);
    }

    private function shiftStartEnd(string $shift, string $date, AttendanceSetting $settings, string $tz): array
    {
        switch ($shift) {
            case 'day':
                $s = $settings->day_shift_start_time;
                $e = $settings->day_shift_end_time;
                break;
            case 'night':
                $s = $settings->night_shift_start_time;
                $e = $settings->night_shift_end_time;
                break;
            case 'relief day':
                $s = $settings->relief_day_shift_start_time;
                $e = $settings->relief_day_shift_end_time;
                break;
            case 'relief night':
                $s = $settings->relief_night_shift_start_time;
                $e = $settings->relief_night_shift_end_time;
                break;
            default:
                abort(422, 'Invalid shift');
        }

        $s = strlen($s) === 5 ? $s . ':00' : $s;
        $e = strlen($e) === 5 ? $e . ':00' : $e;

        $start = Carbon::createFromFormat('Y-m-d H:i:s', $date . ' ' . $s, $tz);
        $end = Carbon::createFromFormat('Y-m-d H:i:s', $date . ' ' . $e, $tz);

        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }
        return [$start, $end];
    }
}
