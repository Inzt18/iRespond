<?php

namespace App\Http\Controllers;

use App\Models\ResponderHotline;
use Illuminate\Http\JsonResponse;

class ResponderHotlineController extends Controller
{
    public function index(): JsonResponse
    {
        $hotlines = ResponderHotline::query()
            ->orderBy('id')
            ->get(['type', 'label', 'phone_number']);

        return response()->json([
            'success' => true,
            'hotlines' => $hotlines,
        ]);
    }
}
