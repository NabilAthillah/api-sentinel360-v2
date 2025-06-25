<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Site;
use DB;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use Storage;

class SiteController extends Controller
{
    public function index()
    {
        try {
            //code...
            $sites = Site::all();

            return response()->json([
                'success' => true,
                'data' => $sites
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
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

            $pathImage = '';
            $pathChart = '';

            if ($request->image) {
                $image = $request->image;
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image));
                $imageName = uniqid() . '.png';
                Storage::disk('public')->put("sites/images/{$imageName}", $imageData);
                $pathImage = "sites/images/{$imageName}";
            }

            if ($request->organisation_chart) {
                $image = $request->organisation_chart;
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image));
                $imageName = uniqid() . '.png';
                Storage::disk('public')->put("sites/charts/{$imageName}", $imageData);
                $pathChart = "sites/charts/{$imageName}";
            }

            $siteId = Uuid::uuid4();

            $sites = Site::create([
                'id' => $siteId,
                'image' => $pathImage,
                'name' => $request->name,
                'email' => $request->email,
                'mcst_number' => $request->mcst_number,
                'ma_name' => $request->ma_name,
                'mobile' => $request->mobile,
                'company_name' => $request->company_name,
                'address' => $request->address,
                'block' => $request->block,
                'unit' => $request->unit,
                'postal_code' => $request->postal_code,
                'lat' => $request->lat,
                'long' => $request->long,
                'organisation_chart' => $pathChart,
            ]);

            if (!$sites) {
                return response()->json([
                    'success' => false,
                    'message' => 'Oops! Something went wrong'
                ], 500);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Site created successfully'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong'
            ], 500);
        }
    }

    public function show($id)
    {
        $site = Site::with('routes')->where('id', $id)->first();

        if (!$site) {
            return response()->json([
                'success' => false,
                'message' => 'Site not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'site' => $site
            ]
        ]);
    }

    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $site = Site::where('id', $id)->first();

            if (!$site) {
                return response()->json([
                    'success' => false,
                    'message' => 'Site not found'
                ], 404);
            }

            $pathImage = '';
            $pathChart = '';

            if ($request->image) {
                if ($site->image != '') {
                    if (!Storage::delete($site->image)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Oops! Something went wrong'
                        ], 500);
                    }
                }

                $image = $request->image;
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image));
                $imageName = uniqid() . '.png';
                Storage::disk('public')->put("sites/images/{$imageName}", $imageData);
                $pathImage = "sites/images/{$imageName}";
            }

            if ($request->organisation_chart) {
                if ($site->organisation_chart != '') {
                    if (!Storage::delete($site->organisation_chart)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Oops! Something went wrong'
                        ], 500);
                    }
                }

                $image = $request->organisation_chart;
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image));
                $imageName = uniqid() . '.png';
                Storage::disk('public')->put("sites/charts/{$imageName}", $imageData);
                $pathChart = "sites/charts/{$imageName}";
            }

            $site->update([
                'image' => $pathImage,
                'name' => $request->name,
                'email' => $request->email,
                'mcst_number' => $request->mcst_number,
                'ma_name' => $request->ma_name,
                'mobile' => $request->mobile,
                'company_name' => $request->company_name,
                'address' => $request->address,
                'block' => $request->block,
                'unit' => $request->unit,
                'postal_code' => $request->postal_code,
                'lat' => $request->lat,
                'long' => $request->long,
                'organisation_chart' => $pathChart,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Site edited successfully'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong'
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $site = Site::where('id', $id)->first();

            if (!$site) {
                return response()->json([
                    'success' => false,
                    'message' => 'Site not found'
                ], 404);
            }

            if ($site->image != '') {
                Storage::delete($site->image);
            }

            if ($site->organisation_chart != '') {
                Storage::delete($site->organisation_chart);
            }

            $site->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Site deleted successfully'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong'
            ], 500);
        }
    }
}
