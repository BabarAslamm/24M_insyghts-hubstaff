<?php
namespace Insyghts\Hubstaff\Services;

use Exception;
use Insyghts\Hubstaff\Models\HubstaffConfig;

class HubstaffConfigService
{
    function __construct(HubstaffConfig $config,
                        HubstaffServerService $serverService)
    {
        $this->hubstaffConfig = $config;
        $this->serverService = $serverService;
        $this->serverTimestamp =  $this->serverService->getTimestamp();
        $this->serverTimeString = $this->serverTimestamp['data'];
    }

    public function getConfig()
    {
        $response = [
            'success' => false, 
            'data'    => "There is some error",
        ];
        try{
            $data = $this->hubstaffConfig->getConfig();
            if(count($data)){
                $response['success'] = true;
                $response['data'] = $data;
            }else{
                $response['success'] = true;
                $response['data'] = "No data found!";
            }
        }catch(Exception $e){
            $show = get_class($e) == 'Illuminate\Database\QueryException' ? false : true;
            if($show){
                $response['data'] = $e->getMessage();
            }
        }finally{
            return $response;
        }
    }

    public function saveConfig($data)
    {
        $response = [
            'success' => false,
            'data' => 'There is some error'
        ];

        $data['created_by'] = app('loginUser')->getUser()->id; 
        $data['last_modified_by'] = app('loginUser')->getUser()->id; 
        $created_at = gmdate('Y-m-d G:i:s', $this->serverTimeString);
        $updated_at = gmdate('Y-m-d G:i:s', $this->serverTimeString);
        $data['created_at'] = $created_at;
        $data['updated_at'] = $updated_at;

        try{
            $result = $this->hubstaffConfig->saveConfig($data);
            if($result){
                $response['success'] = true;
                $response['data'] = $result;
            }
        }catch(Exception $e){
            $show = get_class($e) == 'Illuminate\Database\QueryException' ? false : true;
            if($show){
                $response['data'] = $e->getMessage();
            }
        }finally{
            return $response;
        }
    }

    public function updateConfig($data, $id)
    {
        $response = [
            'success' => false,
            'data' => 'There is some error',
        ];
        
        $updated_at = gmdate('Y-m-d G:i:s', $this->serverTimeString);
        $data['last_modified_by'] = app('loginUser')->getUser()->id; 
        $data['updated_at'] = $updated_at;

        try{
            $result = $this->hubstaffConfig->updateConfig($data, $id, $response);
            if($result){
                $response['success'] = true;
                $response['data'] = $result;
            }
        }catch(Exception $e){
            $show = get_class($e) == 'Illuminate\Database\QueryException' ? false : true;
            if($show){
                $response['data'] = $e->getMessage();
            }
        }finally{
            return $response;
        } 
    }

    public function deleteConfig($id)
    {   
        $response = [
            'success' => false,
            'data' => 'There is some error'
        ];

        try{
            $result = $this->hubstaffConfig->deleteConfig($id, $response);
            if($result){
                $response['success'] = true;
                $response['data'] = "Successfully Deleted!";
            }
        }catch(Exception $e){
            $show = get_class($e) == 'Illuminate\Database\QueryException' ? false : true;
            if($show){
                $response['data'] = $e->getMessage();
            }
        }finally{
            return $response;
        }
    }
}
