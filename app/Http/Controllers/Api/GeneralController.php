<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\General\GeneralRepository;
use Illuminate\Http\Request;

class GeneralController extends Controller
{

    private GeneralRepository $generalRepository;

    public function __construct(GeneralRepository $generalRepository)
    {
        $this->generalRepository = $generalRepository;
    }

    public function keyExchange(Request $request) {

    }
}
