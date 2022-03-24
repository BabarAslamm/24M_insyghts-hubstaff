<?php

namespace Insyghts\Hubstaff\Services;

use Exception;
use Illuminate\Support\Facades\Session;
use Insyghts\Hubstaff\Models\HubstaffConfig;
use Insyghts\Hubstaff\Models\ServerTimestamp;

class HubstaffServerService
{
    function __construct()
    {  
    }

    public function getTimestamp()
    {
        $response = [
            'success' => false,
            'data' => "Something went wrong"
        ];
        $timestring = strtotime(gmdate('Y-m-d G:i:s'));
        // $hubstaffConfig = HubstaffConfig::first();
        // echo '<pre>'; print_r($hubstaffConfig); exit;
        if($timestring){
            $response['success'] = true;
            $response['data'] = $timestring;
        }
       

        return $response;
    }

    public function generateDummyData()
    {
                
    }
}
