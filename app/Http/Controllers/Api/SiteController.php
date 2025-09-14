<?php

namespace App\Http\Controllers\Api;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Site;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

            $site = Site::create([
                'id' => $siteId,
                'image' => $pathImage,
                'name' => $request->name,
                'email' => $request->email,
                'mcst_number' => $request->mcst_number,
                'managing_agent' => $request->managing_agent,
                'person_in_charge' => $request->person_in_charge,
                'mobile' => $request->mobile,
                'address' => $request->address,
                'postal_code' => $request->postal_code,
                'lat' => $request->lat,
                'long' => $request->long,
                'organisation_chart' => $pathChart,
            ]);

            DB::commit();

            AuditLogger::log(
                (Auth::user()->email ?? 'Unknown') . " created site {$site->name}",
                json_encode($site->toArray(), JSON_PRETTY_PRINT),
                'success',
                Auth::id(),
                'create site'
            );

            return response()->json([
                'success' => true,
                'message' => 'Site created successfully'
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            AuditLogger::log(
                "Failed to create site",
                "Error: " . $th->getMessage(),
                'error',
                Auth::id(),
                'create site'
            );

            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong'
            ], 500);
        }
    }

    public function show($id)
    {
        $site = Site::with('routes', 'routes.pointers')->where('id', $id)->first();

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

            $before = $site->toArray();

            $pathImage = $site->image;
            $pathChart = $site->organisation_chart;

            if ($request->image) {
                if ($site->image) {
                    Storage::delete($site->image);
                }

                $image = $request->image;
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image));
                $imageName = uniqid() . '.png';
                Storage::disk('public')->put("sites/images/{$imageName}", $imageData);
                $pathImage = "sites/images/{$imageName}";
            }

            if ($request->organisation_chart) {
                if ($site->organisation_chart) {
                    Storage::delete($site->organisation_chart);
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
                'managing_agent' => $request->managing_agent,
                'person_in_charge' => $request->person_in_charge,
                'mobile' => $request->mobile,
                'address' => $request->address,
                'postal_code' => $request->postal_code,
                'lat' => $request->lat,
                'long' => $request->long,
                'organisation_chart' => $pathChart,
            ]);

            DB::commit();

            $after = $site->toArray();

            $log = "Data before update:\n" . json_encode($before, JSON_PRETTY_PRINT);
            $log .= "\n\nData after update:\n" . json_encode($after, JSON_PRETTY_PRINT);

            AuditLogger::log(
                (Auth::user()->email ?? 'Unknown') . " updated site {$site->name}",
                $log,
                'success',
                Auth::id(),
                'update site'
            );

            return response()->json([
                'success' => true,
                'message' => 'Site edited successfully'
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            AuditLogger::log(
                "Failed to update site",
                "Error: " . $th->getMessage(),
                'error',
                Auth::id(),
                'update site'
            );

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

            $logData = $site->toArray();

            if ($site->image) {
                Storage::delete($site->image);
            }

            if ($site->organisation_chart) {
                Storage::delete($site->organisation_chart);
            }

            $site->delete();

            DB::commit();

            AuditLogger::log(
                (Auth::user()->email ?? 'Unknown') . " deleted site {$logData['name']}",
                "Deleted data:\n" . json_encode($logData, JSON_PRETTY_PRINT),
                'success',
                Auth::id(),
                'delete site'
            );

            return response()->json([
                'success' => true,
                'message' => 'Site deleted successfully'
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            AuditLogger::log(
                "Failed to delete site",
                "Error: " . $th->getMessage(),
                'error',
                Auth::id(),
                'delete site'
            );

            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong'
            ], 500);
        }
    }
}
