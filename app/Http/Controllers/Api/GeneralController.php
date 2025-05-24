<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\General\GeneralRepository;
use App\Services\General\GeneralService;
use Illuminate\Http\Request;

class GeneralController extends Controller
{

    private GeneralService $generalService;

    public function __construct(GeneralService $generalService) {
        $this->generalService = $generalService;
    }

    function getFeatureStatus()
    {

    }
}
