<?php

namespace Insyghts\Hubstaff\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Insyghts\Authentication\Middleware\myAuth;
use Insyghts\Hubstaff\Services\HubstaffConfigService;

class HubstaffConfigController extends Controller
{
    public function __construct(HubstaffConfigService $configService)
    {
        $this->middleware(myAuth::class);
        $this->configService = $configService;
    }

    public function viewConfig()
    {
        $configurations = $this->configService->getConfig();
        return response()->json($configurations);
    }

    public function storeConfig(Request $request)
    {
        $this->validate($request, [
            'screenshot_frequency' => 'required',
            'idle_timeout' => 'required'
        ]);
        $data = $request->all();
        $result = $this->configService->saveConfig($data);
        return response()->json($result);
    }

    public function updateConfig(Request $request, $id)
    {
        $this->validate($request, [
            'screenshot_frequency' => 'required',
            'idle_timeout' => 'required',
        ]);
        $data = $request->all();
        $result = $this->configService->updateConfig($data, $id);
        return response()->json($result);
    }

    public function deleteConfig($id)
    {
        $result = $this->configService->deleteConfig($id);
        return response()->json($result);
    }
}
