<?php

namespace Insyghts\Hubstaff\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HubstaffConfig extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'hubstaff_configs';
    protected $guarded = [];

    public function getConfig()
    {
        return HubstaffConfig::all();
    }

    public function saveConfig($data)
    {
        $inserted = HubstaffConfig::create($data);
        if(!$inserted){
            $inserted = false;
        }
        return $inserted->toArray();
    }

    public function updateConfig($data, $id, &$response)
    {
        $config = HubstaffConfig::find($id);
        if(!$config){
            $response['data'] = "Record with id: {$id} not found!";
        }else{
            $config->screenshot_frequency = $data['screenshot_frequency'];
            $config->idle_timeout = $data['idle_timeout'];
            $config->last_modified_by = $data['last_modified_by'];

            $config->save();
        }
        return $config->toArray();
    }

    public function deleteConfig($id, &$response)
    {
        $config = HubstaffConfig::find($id);
        if(!$config){
            $response['data'] = "Record with id: {$id} not found!";
        }else{
            if($config->delete()){
                $config->deleted_by = app('loginUser')->getUser()->id;
                $config->save();
            }
        }
        return $config;
    }
}
