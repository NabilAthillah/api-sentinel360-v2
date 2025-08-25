<?php

namespace App\Http\Controllers\Api;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\AttendanceSetting;
use App\Models\Site;
use App\Models\SiteUser;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SiteUserController extends Controller
{
    public function index(Request $request)
    {
        $allocationType = $request->allocationType;
        $shiftType = $request->shiftType;
        $dateInput = $request->date;

        $query = SiteUser::with(['user', 'site'])
            ->where('shift', $shiftType);

        if ($allocationType === 'bydate') {
            $date = Carbon::createFromFormat('Y-m-d', $dateInput);
            $query->whereDate('date', $date);

            return response()->json([
                'success' => true,
                'data' => $query->get(),
            ]);
        }

        if ($allocationType === 'bymonth') {
            try {
                if (strlen($dateInput) === 10) {
                    $date = Carbon::createFromFormat('Y-m-d', $dateInput);
                } else {
                    $date = Carbon::createFromFormat('Y-m', $dateInput)->startOfMonth();
                }

                $startOfMonth = $date->copy()->startOfMonth();
                $endOfMonth = $date->copy()->endOfMonth();
                $daysInMonth = $startOfMonth->daysInMonth;

                // Ambil semua SiteUser di bulan dan shift tersebut
                $siteUsers = SiteUser::with(['user', 'site'])
                    ->where('shift', $shiftType)
                    ->whereBetween('date', [$startOfMonth, $endOfMonth])
                    ->get()
                    ->groupBy(fn($item) => $item->id_site . '|' . $item->id_employee) // group per site dan employee
                    ->filter(function ($items) use ($daysInMonth) {
                        return $items->count() === $daysInMonth;
                    })
                    ->flatten();

                return response()->json([
                    'success' => true,
                    'data' => $siteUsers->values(),
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid date format. Use YYYY-MM or YYYY-MM-DD.'
                ], 422);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid allocation type.'
        ], 422);
    }

    public function allocation(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $site = Site::find($id);
            if (!$site) {
                return response()->json([
                    'success' => false,
                    'message' => 'Site not found'
                ], 404);
            }

            $employee = User::find($request->id_employee);
            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found'
                ], 404);
            }

            $allocationType = $request->allocationType;
            $shiftType = $request->shiftType;
            $dateInput = $request->date;

            $allocatedDates = [];

            if ($allocationType === 'bydate') {
                SiteUser::create([
                    'id_user' => $employee->id,
                    'id_site' => $site->id,
                    'shift' => $shiftType,
                    'date' => $dateInput,
                ]);

                $allocatedDates[] = $dateInput;
            } elseif ($allocationType === 'bymonth') {
                $startOfMonth = Carbon::createFromFormat('Y-m', $dateInput)->startOfMonth();
                $endOfMonth = $startOfMonth->copy()->endOfMonth();

                SiteUser::where('id_employee', $employee->id)
                    ->where('id_site', $site->id)
                    ->where('shift', $shiftType)
                    ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
                    ->delete();

                $dates = CarbonPeriod::create($startOfMonth, $endOfMonth);
                foreach ($dates as $date) {
                    SiteUser::create([
                        'id_user' => $employee->id,
                        'id_site' => $site->id,
                        'shift' => $shiftType,
                        'date' => $date->toDateString(),
                    ]);
                    $allocatedDates[] = $date->toDateString();
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid allocation type'
                ], 400);
            }

            DB::commit();

            // Logging
            AuditLogger::log(
                (Auth::user()->email ?? 'Unknown') . " allocated employee {$employee->name} to site {$site->name}",
                json_encode([
                    'Site' => $site->name,
                    'Employee' => $employee->name,
                    'Shift' => $shiftType,
                    'Type' => $allocationType,
                    'Dates' => $allocatedDates,
                ], JSON_PRETTY_PRINT),
                'success',
                Auth::id(),
                'site allocation'
            );

            return response()->json([
                'success' => true,
                'message' => 'Site allocated successfully'
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            AuditLogger::log(
                'Allocation Failed',
                "Error: " . $th->getMessage(),
                'error',
                Auth::id(),
                'site allocation'
            );

            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong' . $th->getMessage()
            ], 500);
        }
    }

    public function disallocation(Request $request)
    {
        try {
            DB::beginTransaction();

            $employee = User::where('id', $request->id_employee)->first();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found'
                ], 404);
            }

            $allocationType = $request->allocationType;
            $shiftType = $request->shiftType;
            $dateInput = $request->date;
            $siteId = $request->siteId;
            $disallocatedDates = [];

            if ($allocationType === 'bydate') {
                $date = Carbon::createFromFormat('Y-m-d', $dateInput);

                SiteUser::where('id_site', $siteId)
                    ->where('id_user', $employee->id)
                    ->where('shift', $shiftType)
                    ->whereDate('date', $date)
                    ->delete();

                $disallocatedDates[] = $date->toDateString();
            } elseif ($allocationType === 'bymonth') {
                try {
                    $date = strlen($dateInput) === 10
                        ? Carbon::createFromFormat('Y-m-d', $dateInput)
                        : Carbon::createFromFormat('Y-m', $dateInput)->startOfMonth();

                    $startOfMonth = $date->copy()->startOfMonth();
                    $endOfMonth = $date->copy()->endOfMonth();

                    $datesToDelete = SiteUser::where('id_site', $siteId)
                        ->where('id_user', $employee->id)
                        ->where('shift', $shiftType)
                        ->whereBetween('date', [$startOfMonth, $endOfMonth])
                        ->pluck('date')
                        ->toArray();

                    SiteUser::where('id_site', $siteId)
                        ->where('id_user', $employee->id)
                        ->where('shift', $shiftType)
                        ->whereBetween('date', [$startOfMonth, $endOfMonth])
                        ->delete();

                    $disallocatedDates = array_map(fn($d) => Carbon::parse($d)->toDateString(), $datesToDelete);
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid date format. Use YYYY-MM or YYYY-MM-DD.'
                    ], 422);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid allocation type.'
                ], 400);
            }

            DB::commit();

            // Logging
            AuditLogger::log(
                (Auth::user()->email ?? 'Unknown') . " disallocated employee {$employee->name} from site {$siteId}",
                json_encode([
                    'Site ID' => $siteId,
                    'Employee' => $employee->name,
                    'Shift' => $shiftType,
                    'Type' => $allocationType,
                    'Dates Removed' => $disallocatedDates,
                ], JSON_PRETTY_PRINT),
                'success',
                Auth::id(),
                'site disallocation'
            );

            return response()->json([
                'success' => true,
                'message' => 'Site disallocated successfully'
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            AuditLogger::log(
                'Disallocation Failed',
                "Error: " . $th->getMessage(),
                'error',
                Auth::id(),
                'site disallocation'
            );

            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong - '
            ], 500);
        }
    }

    public function show($id)
    {
        $data = SiteUser::with('site', 'attendance')->where('id', $id)
            ->first();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function nearest($id)
    {
        $shift = AttendanceSetting::first();

        $tz = 'Asia/Singapore';
        $now = Carbon::now($tz);

        $dayStart = Carbon::today($tz)->setTimeFromTimeString($shift->day_shift_start_time);
        $dayEnd = Carbon::today($tz)->setTimeFromTimeString($shift->day_shift_end_time);

        $relDayStart = Carbon::today($tz)->setTimeFromTimeString($shift->relief_day_shift_start_time);
        $relDayEnd = Carbon::today($tz)->setTimeFromTimeString($shift->relief_day_shift_end_time);

        $nightStartPrev = Carbon::yesterday($tz)->setTimeFromTimeString($shift->night_shift_start_time);
        $nightEndPrev = Carbon::today($tz)->setTimeFromTimeString($shift->night_shift_end_time);

        $relNightStartPrev = Carbon::yesterday($tz)->setTimeFromTimeString($shift->relief_night_shift_start_time);
        $relNightEndPrev = Carbon::today($tz)->setTimeFromTimeString($shift->relief_night_shift_end_time);

        $nightStartToday = Carbon::today($tz)->setTimeFromTimeString($shift->night_shift_start_time);
        $nightEndToday = Carbon::tomorrow($tz)->setTimeFromTimeString($shift->night_shift_end_time);

        $relNightStartToday = Carbon::today($tz)->setTimeFromTimeString($shift->relief_night_shift_start_time);
        $relNightEndToday = Carbon::tomorrow($tz)->setTimeFromTimeString($shift->relief_night_shift_end_time);

        $activeShift = null;
        $scheduleDate = Carbon::today($tz)->toDateString();

        if ($now->betweenIncluded($dayStart, $dayEnd)) {
            $activeShift = 'day';
            $scheduleDate = $dayStart->toDateString();

        } elseif ($now->betweenIncluded($relDayStart, $relDayEnd)) {
            $activeShift = 'relief_day';
            $scheduleDate = $relDayStart->toDateString();

        } elseif ($now->betweenIncluded($nightStartPrev, $nightEndPrev)) {
            $activeShift = 'night';
            $scheduleDate = $nightEndPrev->toDateString();

        } elseif ($now->betweenIncluded($nightStartToday, $nightEndToday)) {
            $activeShift = 'night';
            $scheduleDate = $nightStartToday->toDateString();

        } elseif ($now->betweenIncluded($relNightStartPrev, $relNightEndPrev)) {
            $activeShift = 'relief_night';
            $scheduleDate = $relNightStartPrev->toDateString();

        } elseif ($now->betweenIncluded($relNightStartToday, $relNightEndToday)) {
            $activeShift = 'relief_night';
            $scheduleDate = $relNightStartToday->toDateString();
        }

        $data = SiteUser::with('site')
        ->where('id_user', $id)
            ->whereDate('date', $scheduleDate)
            ->orderBy('date', 'asc')
            ->first();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function data($id)
    {
        $shift = AttendanceSetting::first();

        $tz = 'Asia/Singapore';
        $now = Carbon::now($tz);

        $dayStart = Carbon::today($tz)->setTimeFromTimeString($shift->day_shift_start_time);
        $dayEnd = Carbon::today($tz)->setTimeFromTimeString($shift->day_shift_end_time);

        $relDayStart = Carbon::today($tz)->setTimeFromTimeString($shift->relief_day_shift_start_time);
        $relDayEnd = Carbon::today($tz)->setTimeFromTimeString($shift->relief_day_shift_end_time);

        $nightStartPrev = Carbon::yesterday($tz)->setTimeFromTimeString($shift->night_shift_start_time);
        $nightEndPrev = Carbon::today($tz)->setTimeFromTimeString($shift->night_shift_end_time);

        $relNightStartPrev = Carbon::yesterday($tz)->setTimeFromTimeString($shift->relief_night_shift_start_time);
        $relNightEndPrev = Carbon::today($tz)->setTimeFromTimeString($shift->relief_night_shift_end_time);

        $nightStartToday = Carbon::today($tz)->setTimeFromTimeString($shift->night_shift_start_time);
        $nightEndToday = Carbon::tomorrow($tz)->setTimeFromTimeString($shift->night_shift_end_time);

        $relNightStartToday = Carbon::today($tz)->setTimeFromTimeString($shift->relief_night_shift_start_time);
        $relNightEndToday = Carbon::tomorrow($tz)->setTimeFromTimeString($shift->relief_night_shift_end_time);

        $activeShift = null;
        $scheduleDate = Carbon::today($tz)->toDateString();

        if ($now->betweenIncluded($dayStart, $dayEnd)) {
            $activeShift = 'day';
            $scheduleDate = $dayStart->toDateString();

        } elseif ($now->betweenIncluded($relDayStart, $relDayEnd)) {
            $activeShift = 'relief_day';
            $scheduleDate = $relDayStart->toDateString();

        } elseif ($now->betweenIncluded($nightStartPrev, $nightEndPrev)) {
            $activeShift = 'night';
            $scheduleDate = $nightStartPrev->toDateString();

        } elseif ($now->betweenIncluded($nightStartToday, $nightEndToday)) {
            $activeShift = 'night';
            $scheduleDate = $nightStartToday->toDateString();

        } elseif ($now->betweenIncluded($relNightStartPrev, $relNightEndPrev)) {
            $activeShift = 'relief_night';
            $scheduleDate = $relNightStartPrev->toDateString();

        } elseif ($now->betweenIncluded($relNightStartToday, $relNightEndToday)) {
            $activeShift = 'relief_night';
            $scheduleDate = $relNightStartToday->toDateString();
        }

        $data = SiteUser::with('attendance')
            ->where('id_user', $id)
            ->whereDate('date', $scheduleDate)
            ->orderBy('date', 'asc')
            ->first();

        if ($data) {
            $datas = SiteUser::with('attendance')
                ->where('id_user', $id)
                ->where('id', '<>', $data->id)
                ->where(function ($q) use ($now) {
                    $q->whereDate('date', '<', $now->toDateString())
                        ->orWhere(function ($q2) use ($now) {
                            $q2->whereDate('date', $now->toDateString())
                                ->whereTime('date', '<=', $now->toTimeString());
                        });
                })
                ->orderBy('date', 'asc')
                ->limit(2)
                ->get();
        } else {
            $datas = SiteUser::with('attendance')
                ->where('id_user', $id)
                ->where(function ($q) use ($now) {
                    $q->whereDate('date', '<', $now->toDateString())
                        ->orWhere(function ($q2) use ($now) {
                            $q2->whereDate('date', $now->toDateString())
                                ->whereTime('date', '<=', $now->toTimeString());
                        });
                })
                ->orderBy('date', 'asc')
                ->limit(2)
                ->get();
        }

        return response()->json([
            'success' => true,
            'shift' => $activeShift,
            'date' => $scheduleDate,
            'datas' => $datas,
            'data' => $data,
        ]);
    }
}
