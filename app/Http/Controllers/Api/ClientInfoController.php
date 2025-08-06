<?php

namespace App\Http\Controllers\Api;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\ClientInfo;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Storage;

class ClientInfoController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => ClientInfo::first()
        ]);
    }
    public function update(Request $request, $id)
{
    try {
        DB::beginTransaction();

        $info = ClientInfo::where('id', $id)->first();

        if (!$info) {
            return response()->json([
                'success' => false,
                'message' => 'Client info not found'
            ], 404);
        }

        $before = $info->toArray(); 

        $logDesc = "Data before update:\n";
        foreach ($before as $key => $value) {
            $logDesc .= ucfirst($key) . ": " . ($value ?? '-') . "\n";
        }

        if ($request->logo) {
            if ($info->logo) {
                Storage::delete($info->logo);
            }

            $image = $request->logo;
            $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image));
            $imageName = uniqid() . '.png';
            Storage::disk('public')->put("client/logo/{$imageName}", $imageData);
            $info->logo = "client/logo/{$imageName}";
        }

        if ($request->chart) {
            if ($info->chart) {
                Storage::delete($info->chart);
            }

            $image = $request->chart;
            $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image));
            $imageName = uniqid() . '.png';
            Storage::disk('public')->put("client/charts/{$imageName}", $imageData);
            $info->chart = "client/charts/{$imageName}";
        }

        $info->name = $request->name;
        $info->reg_no = $request->reg_no;
        $info->address = $request->address;
        $info->contact = $request->contact;
        $info->website = $request->website;
        $info->email = $request->email;

        $info->save();

        DB::commit();

        $after = $info->toArray();
        $logDesc .= "\nData after update:\n";
        foreach ($after as $key => $value) {
            $logDesc .= ucfirst($key) . ": " . ($value ?? '-') . "\n";
        }

        AuditLogger::log(
            (Auth::user()->email ?? 'Unknown') . " updated client info",
            $logDesc,
            'success',
            Auth::id()
        );

        return response()->json([
            'success' => true,
            'message' => 'Client info edited successfully'
        ]);
    } catch (\Throwable $th) {
        DB::rollBack();

        AuditLogger::log(
            "Failed to update client info",
            "Error: " . $th->getMessage(),
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
