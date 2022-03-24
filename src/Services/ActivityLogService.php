<?php

namespace Insyghts\Hubstaff\Services;

use Exception;
use Insyghts\Authentication\Helpers\Helpers;
use Insyghts\Authentication\Models\SessionToken;
use Insyghts\Authentication\Models\User;
use Insyghts\Hubstaff\Models\ActivityLog;
use Insyghts\Hubstaff\Models\ActivityScreenShot;
use Insyghts\Hubstaff\Models\AttendanceLog;

class ActivityLogService
{
    function __construct(
        ActivityLog $aLog,
        ActivityScreenShot $aScreenShot,
        AttendanceLog $attendanceLog,
        AttendanceLogService $attendanceLogService,
        User $user,
        SessionToken $sessionToken,
        HubstaffServerService $serverService
    ) {
        $this->actLog = $aLog;
        $this->actScreenShot = $aScreenShot;
        $this->attendanceLog = $attendanceLog;
        $this->attendanceLogService = $attendanceLogService;
        $this->token = Helpers::get_token();
        $this->user = $user;
        $this->sessionToken = $sessionToken;
        $this->serverService = $serverService;
        $this->serverTimestamp =  $this->serverService->getTimestamp();
        $this->serverTimeString = $this->serverTimestamp['data'];
    }

    public function listActivityLog($filter = [])
    {
        $response = [
            'success' => false,
            'data' => 'There is some error'
        ];
        try {
            $result = $this->actLog->listActivityLog($filter);
            if (count($result) > 0) {
                $response['success'] = true;
                $response['data'] = $result['activityLogs'];
            } else {
                $response['data'] = "No records found!";
            }
        } catch (Exception $e) {
            $show = get_class($e) == 'Illuminate\Database\QueryException' ? false : true;
            if ($show) {
                $response['data'] = $e->getMessage();
            }
        } finally {
            return $response;
        }
    }

    public function listActivityScreenshots($filter = [])
    {
        $response = [
            'success' => false,
            'activity_logs' => 'There is some error'
        ];
        try {
            $result = $this->actLog->listActivityLog($filter);
            if (count($result) > 0) {
                $response['success'] = true;
                $result['activityLogs']['data'] = $this->handleIdleLogs($result['activityLogs']['data']);
                $result['activityLogs']['data'] = $this->handleNoActivityCase($result);
                $activityLogChunks = $result['activityLogs']['data'];
                $workingHours = $this->workHours($result['minDate'], $result['maxDate']);
                $response['activity_logs'] = $activityLogChunks;
                $response['work_hours'] = $workingHours;
                $response['user'] = app('loginUser')->getUser()->toArray();
            } else {
                $response['screenshots'] = "No records found!";
            }
        } catch (Exception $e) {
            $show = get_class($e) == 'Illuminate\Database\QueryException' ? false : true;
            if ($show) {
                $response['success'] = false;
                $response['message'] = $e->getMessage();
            }
        } finally {
            return $response;
        }
    }

    public function handleIdleLogs($allLogs)
    {
        $modifiedLogs = [];
        
        for ($i = 0; $i < count($allLogs); $i++) {
            if ($allLogs[$i]['time_type'] == 'I') {
                $idleLogs = $allLogs[$i];
                $logFromDate = gmdate('Y-m-d G:i:00', strtotime($idleLogs['log_from_date']));
                $logToDate = gmdate('Y-m-d G:i:00', strtotime($idleLogs['log_to_date']));
                $incrementLogString = strtotime($logFromDate);
                $plusTenMinString = $incrementLogString + 600;
                $endLogString = strtotime($logToDate);
                $flag = 0;
                while ($incrementLogString < $endLogString) {
                    // For first case
                    $v = gmdate('Y-m-d G:i:s', $incrementLogString);
                    $g = gmdate('Y-m-d G:i:s', $plusTenMinString);
                    $idleLogs['start_time'] = $v;
                    $idleLogs['end_time'] = $g;
                    
                    if ($flag) {
                        $idleLogs['log_from_date'] = $v;
                        $idleLogs['log_to_date'] = $g;
                    }
                    $incrementLogString += 600;
                    $plusTenMinString += 600;
                    array_push($modifiedLogs, $idleLogs);
                    $flag++;
                }
            } else {
                array_push($modifiedLogs, $allLogs[$i]);
            }
        }
        return $modifiedLogs;
    }

    public function handleNoActivityCase($data)
    {
        $minDate = $data['minDate'];
        $maxDate = $data['maxDate'];

        $allLogs = $data['activityLogs']['data'];

        $minDateString = strtotime($minDate);
        $maxDateString = strtotime($maxDate);

        $incrementLogString = strtotime(gmdate('Y-m-d G:i:00', $minDateString));
        $plusTenMinString = $incrementLogString + 600;



        $v = gmdate('Y-m-d G:i:s', $incrementLogString);
        $g = gmdate('Y-m-d G:i:s', $plusTenMinString);

        $modifiedLogs = [];
        $i = 0;
        while ($incrementLogString <= $maxDateString && $i < count($allLogs)) {
            $activityFromString = strtotime(gmdate('Y-m-d G:i:s', strtotime($allLogs[$i]['log_from_date'])));
            $activityToString = strtotime(gmdate('Y-m-d G:i:s', strtotime($allLogs[$i]['log_to_date'])));

            if ($activityFromString >= $incrementLogString && $activityFromString <= $plusTenMinString) {
                if ($allLogs[$i]['time_type'] == "CI" || $allLogs[$i]['time_type'] == "CO") {
                    $incrementLogString -= 600;
                    $plusTenMinString -= 600;
                    array_push($modifiedLogs, $allLogs[$i]);
                } else {
                    $emptyArr = [];
                    $emptyArr = $allLogs[$i];
                    if ($emptyArr['time_type'] != 'I') {
                        $emptyArr['start_time'] = gmdate('Y-m-d G:i:s', $incrementLogString);
                        $emptyArr['end_time'] = gmdate('Y-m-d G:i:s', $plusTenMinString);
                    }
                    array_push($modifiedLogs, $emptyArr);
                }
                $i++;
            } else {
                $emptyArr = [];
                $emptyArr['id'] = "NA";
                $emptyArr['activity_date'] = gmdate('Y-m-d G:i:s', $incrementLogString);
                $emptyArr['log_from_date'] = gmdate('Y-m-d G:i:s', $incrementLogString);
                $emptyArr['log_to_date'] = gmdate('Y-m-d G:i:s', $plusTenMinString);
                $emptyArr['activity'] = "No Activity";
                $emptyArr['time_type'] = "NA";
                $emptyArr['screenshots'] = [];
                $emptyArr['start_time'] = $emptyArr['log_from_date'];
                $emptyArr['end_time'] = $emptyArr['log_to_date'];
                array_push($modifiedLogs, $emptyArr);
            }
            $incrementLogString += 600;
            $plusTenMinString += 600;
        }
        return $modifiedLogs;
    }

    public function workHours($minDate, $maxDate)
    {
        $workHours = [];
        $startingHour = gmdate('Y-m-d G:00:00', strtotime($minDate));
        $incrementHour = $startingHour;
        $incrementHourString = strtotime($incrementHour);
        $endingHour = gmdate('Y-m-d G:i:s', strtotime($maxDate));
        $endString = strtotime($endingHour);
        while ($incrementHourString <= $endString) {
            $i_hour = gmdate('G:00', $incrementHourString);
            $e_hour = gmdate('G:00', strtotime($incrementHour . " + 1 hour"));
            $element = $i_hour . " - " . $e_hour;
            array_push($workHours, $element);
            $incrementHour = gmdate('Y-m-d G:i:s', strtotime($incrementHour . " + 1 hour"));
            $incrementHourString = strtotime($incrementHour);
        }
        return $workHours;
    }

    public function deleteActivityLog($id)
    {
        $response = [
            'success' => false,
            'data' => "There is some error"
        ];

        try {
            $result = $this->actLog->deleteActivityLog($id, $response);
            if ($result) {
                $response['success'] = true;
                $response['data'] = "Record Deleted Successfully!";
            }
        } catch (Exception $e) {
            $show = get_class($e) == 'Illuminate\Database\QueryException' ? false : true;
            if ($show) {
                $response['success'] = false;
                $response['message'] = $e->getMessage();
            }
        } finally {
            return $response;
        }
    }

    public function saveActivityLog($items)
    {
        
        $bulk_insert = [];
        $response = [
            'success' => false,
            'data' => "There is some error",
        ];
        if(isset($items['time_type'])){
            $item = $items;
            $items = [];
            array_push($items, $item);
        }
        foreach($items as $data){   

        if (isset($data['activity_date']) && $data['activity_date'] != NULL) {
            $activity_date = gmdate('Y-m-d G:i:s', strtotime($data['activity_date']));
            $data['activity_date'] = $activity_date;
        } else {
            $data['activity_date'] = gmdate('Y-m-d G:i:s', $this->serverTimeString);
        }
        if (isset($data['log_from_date']) && $data['log_from_date'] != NULL) {
            $log_from_date = gmdate('Y-m-d G:i:s', strtotime($data['log_from_date']));
            $data['log_from_date'] = $log_from_date;
        } else {
            $data['log_from_date'] = gmdate('Y-m-d G:i:s', $this->serverTimeString);
        }
        if (isset($data['log_to_date']) && $data['log_to_date'] != NULL) {
            $log_to_date = gmdate('Y-m-d G:i:s', strtotime($data['log_to_date']));
            $data['log_to_date'] = $log_to_date;
        } else {
            $data['log_to_date'] = gmdate('Y-m-d G:i:s', $this->serverTimeString);
        }
        $created_at = gmdate('Y-m-d G:i:s', $this->serverTimeString);
        $updated_at = gmdate('Y-m-d G:i:s', $this->serverTimeString);

        $user_id = app('loginUser')->getUser()->id;
        // $session_token_id = $this->sessionToken->getSessionToken();
        $session_token_id = 1;
        $data['user_id'] = $user_id;
        $data['session_token_id'] = $session_token_id;
        $data = [
            // currently logged-in user will be here
            'user_id' => $data['user_id'],
            // currently logged-in user session will be here
            'session_token_id' => $data['session_token_id'],
            'activity_date' => $data['activity_date'],
            'log_from_date' => $data['log_from_date'],
            'log_to_date' => $data['log_to_date'],
            'note'  =>  isset($data['note']) ? $data['note']: '',
            'keyboard_track' => isset($data['keyboard_track']) && $data['keyboard_track']!='' ? $data['keyboard_track'] : 0,
            'mouse_track'   => isset($data['mouse_track']) && $data['keyboard_track']!='' ? $data['mouse_track'] : 0,
            'time_type' => $data['time_type'],
            'created_by' => app('loginUser')->getUser()->id,
            'last_modified_by' => app('loginUser')->getUser()->id,
            'deleted_by' => NULL,
            'created_at' => $created_at,
            'updated_at' => $updated_at
        ];

        array_push($bulk_insert, $data);

        }   

        try {
            $activityLog = $this->actLog->saveRecord($bulk_insert);
            if ($activityLog) {
                $response['success'] = true;
                $response['data']  = $activityLog;
            }
        } catch (Exception $e) {
            // $show = get_class($e) == 'Illuminate\Database\QueryException' ? false : true;
            // if ($show) {
                $response['data'] = $e->getMessage();
            // }
        } finally {
            return $response;
            }
        
      
    }

    public function validateActivityLogs($data, &$response)
    {
        $valid = false;
        if ($data != null || count($data) > 0) {
            $previousEntry = $this->attendanceLog->getPreviousEntry($data);
            $valid = $this->attendanceLogService->validateConsecutiveEntry($data, $previousEntry, $response);
        }
        return $valid;
    }
}
