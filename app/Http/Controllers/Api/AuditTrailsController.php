<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditTrails;
use Illuminate\Http\Request;

class AuditTrailsController extends Controller
{
    public function index()
    {
        $logs = AuditTrails::with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Audit logs retrieved successfully.',
            'data' => $logs
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'user_id' => 'required|exists:users,id',
            'status' => 'nullable|string',
            'category' => 'required|string'
        ]);

        $log = AuditTrails::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Audit log created successfully.',
            'data' => $log
        ], 201);
    }
}
