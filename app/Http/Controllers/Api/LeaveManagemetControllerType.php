<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\LeaveManagementType;
use Illuminate\Http\Request;

class LeaveManagemetControllerType extends Controller
{
    public function index(Request $request)
    {
        try {
            $leave_managements = LeaveManagementType::with(['user'])->get();

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

    public function store() {}
}
