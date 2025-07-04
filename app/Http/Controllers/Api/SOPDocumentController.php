<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SOPDocument;
use DB;
use Illuminate\Http\Request;
use Storage;

class SOPDocumentController extends Controller
{
    public function index()
    {
        $sop = SOPDocument::all();

        return response()->json([
            'success' => true,
            'data' => $sop
        ]);
    }

    public function update(Request $request, $id)
    {
        try {
            //code...
            DB::beginTransaction();

            $sop = SOPDocument::where('id', $id)->first();

            if (!$sop) {
                return response()->json([
                    'success' => false,
                    'message' => 'Oops! Something went wrong'
                ], 404);
            }

            $pathImage = '';

            if ($request->document) {
                if ($sop->document != '') {
                    if (!Storage::delete($sop->document)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Oops! Something went wrong'
                        ], 500);
                    }
                }

                $image = $request->document;
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image));
                $imageName = uniqid() . '.png';
                Storage::disk('public')->put("sop_doc/images/{$imageName}", $imageData);
                $pathImage = "sop_doc/images/{$imageName}";


                $sop->update([
                    'document' => $pathImage
                ]);
            }

            $sop->update([
                'name' => $request->name ?? $sop->name,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'SOP Doc updated successfully'
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
            DB::beginTransaction();

            $pathImage = '';

            if ($request->document) {
                $image = $request->document;
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image));
                if ($imageData === false) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid base64 image'
                    ], 400);
                }

                $imageName = uniqid() . '.png';
                Storage::disk('public')->put("sop_doc/images/{$imageName}", $imageData);
                $pathImage = "sop_doc/images/{$imageName}";
            }

            $sop = SOPDocument::create([
                'name' => $request->name,
                'document' => $pathImage,
            ]);

            if (!$sop) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Oops! Something went wrong'
                ], 500);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'SOP Doc created successfully',
                'data' => $sop
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
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

            $sop = SOPDocument::where('id', $id)->first();

            if (!$sop) {
                return response()->json([
                    'success' => false,
                    'message' => 'SOP Document not found'
                ], 404);
            }

            if ($sop->document != '') {
                Storage::delete($sop->document);
            }

            $sop->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'SOP Document deleted successfully'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong'
            ], 500);
        }
    }
}
