<?php

namespace App\Http\Controllers;

use App\Models\SearchLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchLogController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'termo' => ['required', 'string', 'max:255'],
        ]);

        SearchLog::create([
            'termo' => $data['termo'],
            'user_id' => $request->user()?->id,
            'created_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }
}
