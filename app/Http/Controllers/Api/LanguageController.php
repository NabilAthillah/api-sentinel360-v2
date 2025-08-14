<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LanguageController extends Controller
{
    public function index()
    {
        return response()->json([
            ['code' => 'en', 'name' => 'English'],
            ['code' => 'ms', 'name' => 'Malay'],
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'language' => 'required|in:en,ms',
        ]);

        $user = Auth::user();

        if ($user->id != $id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user->language = $request->language;
        $user->save();

        return response()->json([
            'message' => 'Language updated successfully',
            'language' => $user->language
        ]);
    }
}
