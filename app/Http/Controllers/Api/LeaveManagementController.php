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
            $leave_managements = LeaveManagement::with(['user', 'site'])->get();

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
        \Log::info('Payload diterima:', $request->all());
        $request->validate([
            'id_site' => 'required|exists:sites,id',
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
            \Log::info('User ID dari Auth:', ['id' => Auth::id()]);

            $leave = LeaveManagement::create([
                'id_user'        => Auth::id(),
                'id_site'        => $request->id_site,
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
            \Log::info('LeaveManagement Store Called', $request->all());
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

    public function updateStatus(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $leave = LeaveManagement::find($id);

            if (!$leave) {
                AuditLogger::log(
                    "Failed to update leave management status",
                    "Leave with ID $id not found",
                    'error',
                    $request->user()->id ?? null,
                    'update leave management status'
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Leave request not found'
                ], 404);
            }

            $validStatuses = ['pending', 'approve', 'rejected'];
            if (!in_array($request->status, $validStatuses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status value'
                ], 400);
            }

            $oldStatus = $leave->status;

            $leave->update(['status' => $request->status]);

            DB::commit();

            AuditLogger::log(
                "Leave Status Updated",
                "{$request->user()->email} updated leave ID {$leave->id} status from {$oldStatus} to {$request->status}",
                'success',
                $request->user()->id ?? null,
                'update leave management status'
            );

            return response()->json([
                'success' => true,
                'message' => 'Leave status updated successfully',
                'data' => $leave
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            AuditLogger::log(
                "Failed to update leave status",
                "Error: {$th->getMessage()}",
                'error',
                $request->user()->id ?? null,
                'update leave management status'
            );

            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
