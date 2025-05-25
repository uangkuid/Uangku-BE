<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResponse;
use App\Services\General\GeneralService;
use Exception;
use Illuminate\Support\Facades\Log;

class GeneralController extends Controller
{

    private GeneralService $generalService;

    public function __construct(GeneralService $generalService)
    {
        $this->generalService = $generalService;
    }

    function getFeatureStatus()
    {
        try {
            return response()->json(new BaseResponse(200, "Success get feature status", $this->generalService->getFeatureStatus()), 200);
        } catch (Exception $e) {
            Log::error("Failed to get feature status: {$e->getMessage()}");
            return response()->json(new BaseResponse(500, "Failed to get feature status"), 500);
        }
    }

    function getSystemConfig()
    {
        try {
            return response()->json(new BaseResponse(200, "Success get system config", $this->generalService->getSystemConfig()), 200);
        } catch (Exception $e) {
            Log::error("Failed to get system config: {$e->getMessage()}");
            return response()->json(new BaseResponse(500, "Failed to get system config"), 500);
        }
    }
}
