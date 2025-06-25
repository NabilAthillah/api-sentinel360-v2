<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OccurrenceCategory;
use DB;
use Illuminate\Http\Request;

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
            //code...
            DB::beginTransaction();

            $category = OccurrenceCategory::where('id', $id)->first();

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Oops! Something went wrong'
                ], 404);
            }

            $category->update([
                'name' => $request->name ?? $category->name,
                'status' => $request->status ?? $category->status,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully'
            ], 200);

        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'success' => false,
                'message' => 'Oops! Somtehing went wrong',
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            //code...
            DB::beginTransaction();

            $category = OccurrenceCategory::create([
                'name' => $request->name,
            ]);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Oops! Somtehing went wrong'
                ], 500);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully'
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'success' => false,
                'message' => 'Oops! Somtehing went wrong'
            ], 500);
        }
    }
}
