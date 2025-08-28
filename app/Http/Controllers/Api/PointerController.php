<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pointer;
use Illuminate\Http\Request;

class PointerController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => Pointer::where('id_route', $request->id_route)->get()
        ]);
    }
}
