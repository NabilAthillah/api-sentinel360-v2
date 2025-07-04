<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Ramsey\Uuid\Uuid;

class IncidentController extends Controller
{
    public function index()
    {
        try {
            $incidents = Incident::with(['site', 'incidentType'])->get();

            return response()->json([
                'success' => true,
                'data' => $incidents
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
        $incident = Incident::with(['site', 'incidentType'])->find($id);

        if (!$incident) {
            return response()->json([
                'success' => false,
                'message' => 'Incident not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $incident
        ]);
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $imagePath = '';
            if ($request->cctv_image) {
                $image = $request->cctv_image;
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image));
                $imageName = uniqid() . '.png';
                Storage::disk('public')->put("incidents/cctv/{$imageName}", $imageData);
                $imagePath = "incidents/cctv/{$imageName}";
            }

            $incident = Incident::create([
                'id' => Uuid::uuid4(),
                'site_id' => $request->site_id,
                'incident_type_id' => $request->incident_type_id,
                'why_happened' => $request->why_happened,
                'how_happened' => $request->how_happened,
                'persons_involved' => $request->persons_involved,
                'persons_injured' => $request->persons_injured,
                'happened_at' => $request->happened_at,
                'details' => $request->details,
                'ops_incharge' => $request->ops_incharge,
                'reported_to_management' => $request->reported_to_management ?? false,
                'management_report_note' => $request->management_report_note,
                'reported_to_police' => $request->reported_to_police ?? false,
                'police_report_note' => $request->police_report_note,
                'property_damaged' => $request->property_damaged ?? false,
                'damage_note' => $request->damage_note,
                'cctv_image' => $imagePath,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Incident created successfully'
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong'
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $incident = Incident::find($id);
            if (!$incident) {
                return response()->json([
                    'success' => false,
                    'message' => 'Incident not found'
                ], 404);
            }

            $imagePath = $incident->cctv_image;

            if ($request->cctv_image) {
                if ($incident->cctv_image && Storage::disk('public')->exists($incident->cctv_image)) {
                    Storage::disk('public')->delete($incident->cctv_image);
                }

                $image = $request->cctv_image;
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image));
                $imageName = uniqid() . '.png';
                Storage::disk('public')->put("incidents/cctv/{$imageName}", $imageData);
                $imagePath = "incidents/cctv/{$imageName}";
            }

            $incident->update([
                'site_id' => $request->site_id,
                'incident_type_id' => $request->incident_type_id,
                'why_happened' => $request->why_happened,
                'how_happened' => $request->how_happened,
                'persons_involved' => $request->persons_involved,
                'persons_injured' => $request->persons_injured,
                'happened_at' => $request->happened_at,
                'details' => $request->details,
                'ops_incharge' => $request->ops_incharge,
                'reported_to_management' => $request->reported_to_management ?? false,
                'management_report_note' => $request->management_report_note,
                'reported_to_police' => $request->reported_to_police ?? false,
                'police_report_note' => $request->police_report_note,
                'property_damaged' => $request->property_damaged ?? false,
                'damage_note' => $request->damage_note,
                'cctv_image' => $imagePath,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Incident updated successfully'
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
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

            $incident = Incident::find($id);
            if (!$incident) {
                return response()->json([
                    'success' => false,
                    'message' => 'Incident not found'
                ], 404);
            }

            if ($incident->cctv_image && Storage::disk('public')->exists($incident->cctv_image)) {
                Storage::disk('public')->delete($incident->cctv_image);
            }

            $incident->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Incident deleted successfully'
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong'
            ], 500);
        }
    }
}
