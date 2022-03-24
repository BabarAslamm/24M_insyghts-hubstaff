<?php

namespace Insyghts\Hubstaff\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Insyghts\Authentication\Middleware\myAuth;
use Insyghts\Hubstaff\Services\HubstaffConfigService;
use Insyghts\Hubstaff\Services\HubstaffServerService;

class HubstaffServerController extends Controller
{
    public function __construct(HubstaffServerService $serverService)
    {
        $this->middleware(myAuth::class);
        $this->serverService = $serverService;
    }

    public function getTimestamp()
    {
        // Server time TimeZONE UTC
        $timeString =  $this->serverService->getTimestamp();
        // $tme = (int)$timeString['data'];
        // echo '<pre>'; print_r(gmdate('Y-m-d G:i:s', $tme)); exit;
        return response()->json($timeString);
    }

    public function generateDummyData()
    {
        $result = $this->serverService->generateDummyData();
        
    }
}
