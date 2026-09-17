<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        // Liveness only; database and storage readiness are separate concerns.
        return response()->json(['data' => [
            'status' => 'ok',
            'service' => 'portfolio-api',
            'version' => 'v1',
        ]]);
    }
}
