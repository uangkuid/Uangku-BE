<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResponse;
use App\Services\General\GeneralService;
use Exception;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class GeneralController extends Controller
{
    private GeneralService $generalService;

    public function __construct(GeneralService $generalService)
    {
        $this->generalService = $generalService;
    }

    #[OA\Get(
        path: '/general/feature-status',
        summary: 'Get feature flags',
        description: 'Public endpoint returning which app features are currently enabled/disabled.',
        tags: ['General'],
        responses: [
            new OA\Response(response: 200, description: 'Success', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function getFeatureStatus()
    {
        try {
            return response()->json(new BaseResponse(200, 'Success get feature status', $this->generalService->getFeatureStatus()), 200);
        } catch (Exception $e) {
            Log::error("Failed to get feature status: {$e->getMessage()}");

            return response()->json(new BaseResponse(500, 'Failed to get feature status'), 500);
        }
    }

    #[OA\Get(
        path: '/general/system-config',
        summary: 'Get system configuration',
        description: 'Public endpoint returning public-facing system configuration values.',
        tags: ['General'],
        responses: [
            new OA\Response(response: 200, description: 'Success', content: new OA\JsonContent(ref: '#/components/schemas/BaseResponse')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function getSystemConfig()
    {
        try {
            return response()->json(new BaseResponse(200, 'Success get system config', $this->generalService->getSystemConfig()), 200);
        } catch (Exception $e) {
            Log::error("Failed to get system config: {$e->getMessage()}");

            return response()->json(new BaseResponse(500, 'Failed to get system config'), 500);
        }
    }
}
