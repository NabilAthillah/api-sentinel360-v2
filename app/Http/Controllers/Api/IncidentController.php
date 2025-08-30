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
use Illuminate\Validation\Rules\File as FileRule;
use Str;
use Throwable;

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
            $validated = $request->validate([
                'incident_type_id'     => ['required','string','exists:incident_types,id'],
                'occurred_at'       => ['required','date'],
                'location'          => ['nullable','string','max:255'],

                'why_happened'      => ['nullable','string'],
                'how_happened'      => ['nullable','string'],
                'person_involved'   => ['nullable','string','max:255'],
                'person_injured'    => ['nullable','string','max:255'],

                'management_report' => ['nullable','in:0,1'],
                'police_report'     => ['nullable','in:0,1'],
                'damage_property'   => ['nullable','in:0,1'],
                'picture_attached'  => ['nullable','in:0,1'],
                'cctv_footage'      => ['nullable','in:0,1'],

                'remarks'           => ['nullable','string'],
                'detail'            => ['nullable','string'],
                'acknowledged_by'   => ['nullable','string','max:255'],

'images.*' => ['nullable', FileRule::image()->max(5 * 1024)],
            ]);

            // siapkan bool (frontend kirim "1"/"0")
            $bool = fn($key) => (int)($request->input($key, 0)) === 1;

            $images = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $img) {
                    if (!$img) continue;
                    // simpan di storage/app/public/incidents
                    $path = $img->store('incidents', 'public');
                    $images[] = $path;
                }
            }

            DB::beginTransaction();

            $incident = Incident::create([
                'id'                => Str::uuid(),
                'id_user'           => Auth::id(), // null jika tidak pakai auth
                'id_incident_type'     => $validated['incident_type_id'],
                'reported_date'       => $validated['occurred_at'],
                'location'          => $validated['location'] ?? null,

                'why_happened'      => $validated['why_happened'] ?? null,
                'how_happened'      => $validated['how_happened'] ?? null,
                'person_involved'   => $validated['person_involved'] ?? null,
                'person_injured'    => $validated['person_injured'] ?? null,

                'management_report' => $bool('management_report'),
                'police_report'     => $bool('police_report'),
                'damage_property'   => $bool('damage_property'),
                'picture_attached'  => $bool('picture_attached'),
                'cctv_footage'      => $bool('cctv_footage'),

                'remarks'           => $validated['remarks'] ?? null,
                'detail'            => $validated['detail'] ?? null,
                'acknowledged_by'   => $validated['acknowledged_by'] ?? null,

                'images'            => $images ?: null,
            ]);

            DB::commit();

            // optionally ubah ke URL publik
            if ($incident->images) {
                $incident->images = array_map(
                    fn($p) => Storage::disk('public')->url($p),
                    $incident->images
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Incident created',
                'data' => $incident,
            ], 201);
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 422);
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
                    $request->user()->id ?? null,
                    'update incident'
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
                $request->user()->id ?? null,
                'update incident'
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
                $request->user()->id ?? null,
                'update incident'
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
                    Auth::id(),
                    'delete incident'
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
                Auth::id(),
                'delete incident'
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
                Auth::id(),
                'delete incident'
            );
            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong'
            ], 500);
        }
    }
}
