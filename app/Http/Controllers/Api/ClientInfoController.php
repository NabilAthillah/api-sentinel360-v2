<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientInfo;
use DB;
use Illuminate\Http\Request;
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

            $pathImage = '';
            $pathChart = '';

            if ($request->logo) {
                if ($info->logo != '') {
                    Storage::delete($info->logo);
                }

                $image = $request->logo;
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image));
                $imageName = uniqid() . '.png';
                Storage::disk('public')->put("sites/logo/{$imageName}", $imageData);
                $pathImage = "client/logo/{$imageName}";
            }

            if ($request->chart) {
                if ($info->chart != '') {
                    Storage::delete($info->chart);
                }

                $image = $request->chart;
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image));
                $imageName = uniqid() . '.png';
                Storage::disk('public')->put("client/charts/{$imageName}", $imageData);
                $pathChart = "client/charts/{$imageName}";
            }

            $info->update([
                'name' => $request->name,
                'reg_no' => $request->reg_no,
                'address' => $request->address,
                'contact' => $request->contact,
                'website' => $request->website,
                'email' => $request->email,
                'logo' => $pathImage,
                'chart' => $pathChart,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Client info edited successfully'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong'
            ], 500);
        }
    }
}
