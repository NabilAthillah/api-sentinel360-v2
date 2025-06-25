<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Occurrence;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;

class OccurrenceController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => Occurrence::with('site', 'category', 'reported_by')->get()
        ], 200);
    }

    public function store(Request $request)
    {
        try {
            //code...
            DB::beginTransaction();

            $data = collect($request->occurrences)->map(function ($item) {
                $dt = Carbon::createFromFormat('Y-m-d\TH:i', $item['occurred_at']);

                return [
                    'id' => Uuid::uuid4(),
                    'id_site' => $item['id_site'],
                    'id_category' => $item['id_category'],
                    'id_user' => auth()->id(),
                    'date' => $dt->toDateString(),
                    'time' => $dt->toTimeString(),
                    'detail' => $item['detail'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            Occurrence::insert($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Occurrence created successfully'
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'success' => false,
                'message' => 'Oops! Somtehing went wrong' . $th->getMessage(),
            ], 500);
        }
    }
}
