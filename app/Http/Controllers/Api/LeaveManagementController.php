<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\LeaveManagement;
use Illuminate\Http\Request;
use DB;
use App\Helpers\AuditLogger;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class LeaveManagementController extends Controller
{
    public function index(Request $request)
    {
        try {
            $leave_managements = LeaveManagement::get();

            return response()->json([
                'success' => true,
                'data' => $leave_managements
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'reason' => 'nullable|string',
            'from' => 'required|date',
            'to'   => 'required|date|after_or_equal:from',
        ]);

        try {
            DB::beginTransaction();
            $from = Carbon::parse($request->from);
            $to   = Carbon::parse($request->to);
            $total = $from->diffInDays($to) + 1;

            $leave = LeaveManagement::create([
                'id_user'        => Auth::id(),
                'type'           => $request->type,
                'from'           => $request->from,
                'to'             => $request->to,
                'total'          => $total,
                'reason'        =>  $request->reason,
                'status'         => 'pending',
            ]);

            if (!$leave) {
                AuditLogger::log(
                    'Create Leave Failed',
                    "Failed to create leave request from {$request->from} to {$request->to}.",
                    'error',
                    $request->user()->id ?? null,
                    'create leave management'
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Oops! Something went wrong'
                ], 500);
            }

            AuditLogger::log(
                'Leave Created',
                "Created leave request ({$leave->id}) for user ID: {$leave->id_user}, from {$leave->from} to {$leave->to}, total {$leave->total} days.",
                'success',
                $request->user()->id ?? null,
                'create leave management'
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Leave request created successfully',
                'data'    => $leave
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            AuditLogger::log(
                'Create Leave Failed',
                'Exception: ' . $th->getMessage(),
                'error',
                $request->user()->id ?? null,
                'create leave management'
            );

            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong. ' . $th->getMessage()
            ], 500);
        }
    }
}
