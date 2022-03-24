<?php

namespace Insyghts\Hubstaff\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Insyghts\Common\Models\BaseModel;

class ActivityLog extends Model
{
    use HasFactory, SoftDeletes;
    protected $guarded = [];

    public function screenshots()
    {
        return $this->hasMany(ActivityScreenShot::class, 'activity_log_id', 'id');
    }

    public function listActivityLog($filters = [])
    {
        $from = '';
        $limit = !empty($filters['limit']) ? $filters['limit'] : 30;
        $actLogsQuery = ActivityLog::with(['screenshots']);
        $actLogsQuery->orderBy('id', 'asc');


        if (count($filters) > 0) {
            // [
            //     {
            //     "key":"name"
            //     "condition":"like"
            //     "value":"awheed"
            //     },{
            //     "key":"age"
            //     "condition":">",
            //     "value":27
            //     }
            // ]
            foreach ($filters as $filter) {
                if ($filter['condition'] == 'between') {
                    $from = gmdate('Y-m-d 01:00:00', strtotime($filter['value']));
                    $to = gmdate('Y-m-d 23:59:59', strtotime($filter['value']));
                    $actLogsQuery->whereBetween($filter['key'], [$from, $to]);
                } elseif ($filter['condition'] == 'like' || $filter['condition'] == 'LIKE') {
                    $actLogsQuery->where($filter['key'], $filter['condition'], "%{$filter['value']}%");
                } else {
                    $actLogsQuery->where($filter['key'], $filter['condition'], $filter['value']);
                }
            }
        }
        if ($from == '') {
            $from = gmdate('Y-m-d 01:00:00');
            $to = gmdate('Y-m-d 23:59:59');
            $actLogsQuery->whereBetween('activity_date', [$from, $to]);
        }
        $actLogs = $actLogsQuery->paginate($limit)->toArray();
        $actLogs = $this->appendBucketUrl($actLogs);
        $minDate = ActivityLog::whereBetween('log_from_date', [$from, $to])->min('log_from_date');
        $maxDate = ActivityLog::whereBetween('log_to_date', [$from, $to])->max('log_to_date');

        return ['activityLogs' => $actLogs, 'minDate' => $minDate, 'maxDate' => $maxDate];
    }

    public function appendBucketUrl($actLogs)
    {
        $bucketUrl = "https://insyghts-dev-db.s3.us-east-1.amazonaws.com";
        $logs = $actLogs['data'];
        foreach ($logs as $key => $actLog) {
            foreach ($actLog['screenshots'] as $key1 => $screenshot) {
                $img_path = $actLog['screenshots'][$key1]['image_path'];
                $logs[$key]['screenshots'][$key1]['image_path'] = $bucketUrl . DIRECTORY_SEPARATOR . $img_path;
            }
        }
        $actLogs['data'] = $logs;
        return $actLogs;
    }

    public function saveRecord($data)
    {
        // $activityLogs = [];
        // array_push($activityLogs, $data);
        $inserted = ActivityLog::insert($data);
        if ($inserted) {
            $inserted = ActivityLog::latest()->first();
        }
        return $inserted;
    }

    public function deleteActivityLog($id, &$response)
    {
        $result = false;
        $id = explode(',', $id);
        $actLog = ActivityLog::whereIn('id', $id)->get();
        if (!count($actLog)) {
            $response['data'] = "Unable to delete, something went wrong!";
        }
        foreach ($actLog as $log) {
            if (isset($log->screenshots)) {
                $log->screenshots()->delete();
                foreach ($log->screenshots as $screenshot) {
                    $screenshot->deleted_by = app('loginUser')->getUser()->id;
                    $screenshot->save();
                }
            }
        }
        if (ActivityLog::whereIn('id', $id)->delete()) {
            foreach ($actLog as $log) {
                $log->deleted_by = app('loginUser')->getUser()->id;
                $log->save();
            }
            $result = true;
        }

        return $result;
    }
}
