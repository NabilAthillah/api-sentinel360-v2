<?php

namespace App\Http\Controllers\Api;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\OccurrenceCategory;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OccurrenceCategoryController extends Controller
{
    public function index()
    {
        $categories = OccurrenceCategory::all();

        return response()->json([
            'success' => true,
            'data' => [
                'categories' => $categories
            ]
        ]);
    }

    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $category = OccurrenceCategory::find($id);

            if (!$category) {
                AuditLogger::log(
                    "Failed to update occurrence category",
                    "Category with ID $id not found",
                    'error',
                    Auth::id()
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Category not found'
                ], 404);
            }

            $oldData = $category->toArray();

            $category->update([
                'name' => $request->name ?? $category->name,
                'status' => $request->status ?? $category->status,
            ]);

            DB::commit();

            $newData = $category->toArray();
            $description = "Updated Occurrence Category (ID: $id)\n";
            $description .= "Before:\n";
            foreach ($oldData as $key => $value) {
                $description .= ucfirst($key) . ": " . $value . "\n";
            }
            $description .= "After:\n";
            foreach ($newData as $key => $value) {
                $description .= ucfirst($key) . ": " . $value . "\n";
            }

            AuditLogger::log(
                "Occurrence category updated by " . (Auth::user()->email ?? 'Unknown'),
                $description,
                'success',
                Auth::id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully'
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            AuditLogger::log(
                "Exception while updating occurrence category",
                "Error: {$th->getMessage()}",
                'error',
                Auth::id()
            );

            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong'
            ], 500);
        }
    }


    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $category = OccurrenceCategory::create([
                'name' => $request->name,
            ]);

            if (!$category) {
                AuditLogger::log(
                    "Failed to create occurrence category",
                    "Attempted with name: {$request->name}",
                    'error',
                    Auth::id()
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Oops! Something went wrong'
                ], 500);
            }

            DB::commit();

            AuditLogger::log(
                "Occurrence category created by " . (Auth::user()->email ?? 'Unknown'),
                "Category created with ID: {$category->id}, Name: {$category->name}",
                'success',
                Auth::id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully'
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            AuditLogger::log(
                "Exception while creating occurrence category",
                "Error: {$th->getMessage()}",
                'error',
                Auth::id()
            );

            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong'
            ], 500);
        }
    }
}
