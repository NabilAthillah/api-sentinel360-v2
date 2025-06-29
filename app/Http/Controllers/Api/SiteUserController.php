<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Site;
use App\Models\SiteUser;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use DB;
use Illuminate\Http\Request;

class SiteUserController extends Controller
{
    public function index(Request $request)
    {
        $allocationType = $request->allocationType;
        $shiftType = $request->shiftType;
        $dateInput = $request->date;

        $query = SiteUser::with(['employee.user', 'site'])
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
                $siteUsers = SiteUser::with(['employee.user', 'site'])
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

            $employee = Employee::find($request->id_employee);
            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found'
                ], 404);
            }

            $allocationType = $request->allocationType;
            $shiftType = $request->shiftType;
            $dateInput = $request->date;

            if ($allocationType === 'bydate') {
                SiteUser::create([
                    'id_employee' => $employee->id,
                    'id_site' => $site->id,
                    'shift' => $shiftType,
                    'date' => $dateInput,
                ]);
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
                        'id_employee' => $employee->id,
                        'id_site' => $site->id,
                        'shift' => $shiftType,
                        'date' => $date->toDateString(),
                    ]);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid allocation type'
                ], 400);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Site allocated successfully'
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong: '
            ], 500);
        }
    }

    public function disallocation(Request $request)
    {
        try {
            DB::beginTransaction();

            $employee = Employee::where('id', $request->id_employee)->first();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found'
                ], 404);
            }

            $allocationType = $request->allocationType;
            $shiftType = $request->shiftType;
            $dateInput = $request->date;

            if ($allocationType === 'bydate') {
                $date = Carbon::createFromFormat('Y-m-d', $dateInput);

                SiteUser::where('id_site', $request->siteId)
                    ->where('id_employee', $employee->id)
                    ->where('shift', $shiftType)
                    ->whereDate('date', $date)
                    ->delete();
            } elseif ($allocationType === 'bymonth') {
                try {
                    $date = strlen($dateInput) === 10
                        ? Carbon::createFromFormat('Y-m-d', $dateInput)
                        : Carbon::createFromFormat('Y-m', $dateInput)->startOfMonth();

                    $startOfMonth = $date->copy()->startOfMonth();
                    $endOfMonth = $date->copy()->endOfMonth();

                    SiteUser::where('id_site', $request->siteId)
                        ->where('id_employee', $employee->id)
                        ->where('shift', $shiftType)
                        ->whereBetween('date', [$startOfMonth, $endOfMonth])
                        ->delete();
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

            return response()->json([
                'success' => true,
                'message' => 'Site disallocated successfully'
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong - '
            ], 500);
        }
    }
}
