<?php

namespace App\Http\Controllers\Api;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Incident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Auth;


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

            AuditLogger::log(
                'Incident Created',
                "New Employee Created:\n" .
                    "ID: {$incident->id}\n" .
                    "Site ID: {$incident->site_id}\n" .
                    "Incident Type ID: {$incident->incident_type_id}\n" .
                    "Why Happened: {$incident->why_happened}\n" .
                    "How Happened: {$incident->how_happened}\n" .
                    "Persons Involved: {$incident->persons_involved}\n" .
                    "Persons Injured: {$incident->persons_injured}\n" .
                    "Happened At: {$incident->happened_at}\n" .
                    "Details: {$incident->details}\n" .
                    "Ops Incharge: {$incident->ops_incharge}\n" .
                    "Reported To Management: {$incident->reported_to_management}\n" .
                    "Management Report Note: {$incident->management_report_note}\n" .
                    "Reported To Police: {$incident->reported_to_police}\n" .
                    "Police Report Note: {$incident->police_report_note}\n" .
                    "Property Damaged: {$incident->property_damaged}\n" .
                    "Damage Note: {$incident->damage_note}\n" .
                    "CCTV Image: {$incident->$imagePath}\n",
                'success',
                $request->user()->id ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Incident created successfully'
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            AuditLogger::log(
                'Incident Creation Failed',
                "Error: " . $th->getMessage(),
                'error',
                $request->user()->id ?? null
            );

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
                AuditLogger::log(
                    "Failed to update incident",
                    "Incident with ID $id not found",
                    'error',
                    $request->user()->id ?? null
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Incident not found'
                ], 404);
            }

            $originalIncident = $incident->replicate();
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

            $description = "{$request->user()->email} updated incident ID: {$id}\n\n";
            $description .= "Data before update:\n";
            $description .= "Site ID: {$originalIncident->site_id}\n";
            $description .= "Incident Type ID: {$originalIncident->incident_type_id}\n";
            $description .= "Why Happened: {$originalIncident->why_happened}\n";
            $description .= "How Happened: {$originalIncident->how_happened}\n";
            $description .= "Persons Involved: {$originalIncident->persons_involved}\n";
            $description .= "Persons Injured: {$originalIncident->persons_injured}\n";
            $description .= "Happened At: {$originalIncident->happened_at}\n";
            $description .= "Details: {$originalIncident->details}\n";
            $description .= "Ops Incharge: {$originalIncident->ops_incharge}\n";
            $description .= "Reported to Management: " . ($originalIncident->reported_to_management ? 'Yes' : 'No') . "\n";
            $description .= "Management Report Note: {$originalIncident->management_report_note}\n";
            $description .= "Reported to Police: " . ($originalIncident->reported_to_police ? 'Yes' : 'No') . "\n";
            $description .= "Police Report Note: {$originalIncident->police_report_note}\n";
            $description .= "Property Damaged: " . ($originalIncident->property_damaged ? 'Yes' : 'No') . "\n";
            $description .= "Damage Note: {$originalIncident->damage_note}\n";
            $description .= "CCTV Image Path: {$originalIncident->cctv_image}\n\n";

            $description .= "Data after update:\n";
            $description .= "Site ID: {$incident->site_id}\n";
            $description .= "Incident Type ID: {$incident->incident_type_id}\n";
            $description .= "Why Happened: {$incident->why_happened}\n";
            $description .= "How Happened: {$incident->how_happened}\n";
            $description .= "Persons Involved: {$incident->persons_involved}\n";
            $description .= "Persons Injured: {$incident->persons_injured}\n";
            $description .= "Happened At: {$incident->happened_at}\n";
            $description .= "Details: {$incident->details}\n";
            $description .= "Ops Incharge: {$incident->ops_incharge}\n";
            $description .= "Reported to Management: " . ($incident->reported_to_management ? 'Yes' : 'No') . "\n";
            $description .= "Management Report Note: {$incident->management_report_note}\n";
            $description .= "Reported to Police: " . ($incident->reported_to_police ? 'Yes' : 'No') . "\n";
            $description .= "Police Report Note: {$incident->police_report_note}\n";
            $description .= "Property Damaged: " . ($incident->property_damaged ? 'Yes' : 'No') . "\n";
            $description .= "Damage Note: {$incident->damage_note}\n";
            $description .= "CCTV Image Path: {$incident->cctv_image}\n";

            AuditLogger::log(
                "Incident updated by {$request->user()->email}",
                $description,
                'success',
                $request->user()->id ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Incident updated successfully'
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            AuditLogger::log(
                "Failed to update incident",
                "Error: {$th->getMessage()}",
                'error',
                $request->user()->id ?? null
            );

            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong',
                'error' => $th->getMessage()
            ], 500);
        }
    }


    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $incident = Incident::find($id);
            if (!$incident) {
                AuditLogger::log(
                    "Failed to delete incident",
                    "Incident with ID $id not found",
                    'error',
                    Auth::id()
                );
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
            AuditLogger::log(
                "Incident deleted by " . (Auth::user()->email ?? 'Unknown'),
                "Incident with ID $id deleted",
                'success',
                Auth::id()
            );
            return response()->json([
                'success' => true,
                'message' => 'Incident deleted successfully'
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            AuditLogger::log(
                "Failed to delete incident",
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
