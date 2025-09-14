<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GuardTour;
use App\Models\Pointer;
use DB;
use Illuminate\Http\Request;
use Log;
use Str;

class GuardTourController extends Controller
{
    public function index(Request $request)
    {
        // Validasi ringan (sesuaikan rules kalau perlu strict)
        $request->validate([
            'site_id' => ['nullable', 'string'],
            'route_id' => ['nullable', 'string'],
            'user_id' => ['nullable', 'string'],
            'date' => ['nullable', 'date'],
        ]);

        $siteId = $request->input('site_id');
        $routeId = $request->input('route_id');
        $userId = $request->input('user_id'); // opsional: $userId ??= (string)auth()->id();
        $date = $request->input('date') ?: now()->toDateString();

        // Base query + window function untuk mengambil baris terbaru per point_id
        // Catatan: butuh MySQL 8 / MariaDB yang support window function.
        $base = GuardTour::query()
            ->from('guard_tours as gt')
            ->join('pointers as pt', 'pt.id', '=', 'gt.point_id')
            ->when($siteId, fn($q) => $q->where('pt.id_site', $siteId))
            ->when($routeId, fn($q) => $q->where('pt.id_route', $routeId))
            ->when($userId, fn($q) => $q->where('gt.user_id', $userId))
            ->whereDate('gt.created_at', $date)
            ->selectRaw('
                gt.id,
                gt.point_id,
                gt.user_id,
                gt.status,
                gt.reason,
                gt.updated_at,
                ROW_NUMBER() OVER (PARTITION BY gt.point_id ORDER BY gt.updated_at DESC, gt.id DESC) as rn
            ');

        // bungkus jadi subquery supaya bisa where rn = 1
        $rows = DB::query()
            ->fromSub($base, 't')
            ->where('t.rn', 1)
            ->get([
                't.point_id',
                't.status',
                't.reason',
                't.updated_at',
            ]);

        // Susun payload "items" sederhana untuk front-end
        $items = $rows->map(function ($r) {
            return [
                'pointer_id' => (string) $r->point_id,
                'status' => (string) $r->status,
                'reason' => $r->reason,
                'updated_at' => (string) $r->updated_at,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $items,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'point_id' => ['required', 'uuid', 'exists:pointers,id'],
            'action' => ['required', 'in:scan,skip'],
            'nfc_serial' => ['required_if:action,scan', 'nullable', 'string', 'max:200'],
            'reason' => ['required_if:action,skip', 'nullable', 'string', 'max:200'],
        ]);

        Log::info($request->all());

        $user = $request->user(); // auth:sanctum
        $point = Pointer::with(['route.site'])->findOrFail($validated['point_id']);

        $redirectUrl = null;
        // bangun redirect berdasarkan relasi point->route->site
        if ($point->route && $point->route->site) {
            $redirectUrl = "/user/clocking/{$point->route->site->id}/route/{$point->route->id}";
        }

        if ($validated['action'] === 'scan') {
            // Normalisasi string untuk bandingkan NFC
            $expected = Str::lower(preg_replace('/\s+/', '', (string) ($point->nfc_tag ?? '')));
            $got = Str::lower(preg_replace('/\s+/', '', (string) ($validated['nfc_serial'] ?? '')));

            if ($expected && $got && hash_equals($expected, $got)) {
                $row = GuardTour::create([
                    'point_id' => $point->id,
                    'user_id' => $user->id,
                    'status' => 'scanned',
                    'reason' => null,
                ]);

                return response()->json([
                    'ok' => true,
                    'data' => [
                        'id' => $row->id,
                        'status' => $row->status,
                    ],
                    'redirect_url' => $redirectUrl, // bisa null kalau relasi belum lengkap
                    'toast' => 'NFC tag verified.',
                ]);
            }

            return response()->json([
                'ok' => false,
                'error_code' => 'WRONG_TAG',
                'message' => 'Wrong NFC tag for this point.',
            ], 422);
        }

        // action === 'skip'
        $row = GuardTour::create([
            'point_id' => $point->id,
            'user_id' => $user->id,
            'status' => 'skipped',
            'reason' => $validated['reason'] ?? null,
        ]);

        return response()->json([
            'ok' => true,
            'data' => [
                'id' => $row->id,
                'status' => $row->status,
            ],
            'redirect_url' => $redirectUrl,
            'toast' => 'Skip recorded.',
        ]);
    }
}
