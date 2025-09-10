<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\LeaveManagementType;
use Illuminate\Http\Request;

class LeaveManagementTypeController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => LeaveManagementType::all()
        ]);
    }
    public function store(Request $request)
    {
        $data = $request->all();

        if (isset($data[0]) && is_array($data[0])) {
            $validated = collect($data)->map(function ($item) {
                return validator($item, [
                    'name' => 'required|string|max:255|unique:leave_managements_type,name',
                    'status' => 'required|in:active,deactive',
                ])->validate();
            });

            $leaveTypes = LeaveManagementType::insert($validated->toArray());

            return response()->json([
                'message' => 'Leave types created successfully',
                'data' => $leaveTypes,
            ], 201);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:leave_managements_type,name',
            'status' => 'required|in:active,deactive',
        ]);

        $leaveType = LeaveManagementType::create($validated);

        return response()->json(['success' => true, 'data' => $leaveType], 201);
    }
}
