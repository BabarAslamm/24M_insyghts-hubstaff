<?php

namespace Insyghts\Hubstaff\Services;

use Exception;
use Insyghts\Authentication\Helpers\Helpers;
use Insyghts\Authentication\Models\SessionToken;
use Insyghts\Authentication\Models\User;
use Insyghts\Hubstaff\Models\ActivityLog;
use Insyghts\Hubstaff\Models\Attendance;
use Insyghts\Hubstaff\Models\AttendanceLog;

class AttendanceLogService
{

    function __construct(
        Attendance $attendance,
        AttendanceLog $attendanceLog,
        ActivityLog $activityLog,
        User $user,
        SessionToken $sessionToken,
        HubstaffServerService $serverService
    ) {
        $this->attendance = $attendance;
        $this->attendanceLog = $attendanceLog;
        $this->activityLog = $activityLog;
        $this->user = $user;
        $this->sessionToken = $sessionToken;
        $this->serverService = $serverService;
        $this->serverTimestamp =  $this->serverService->getTimestamp();
        $this->serverTimeString = $this->serverTimestamp['data'];
    }

    public function saveAttendanceLog($data)
    {
        $response = [
            'success' => 0,
            'data' => 'There is some error while saving'
        ];
        if (isset($data['attendance_date']) && $data['attendance_date'] != NULL) {
            $attendance_date = gmdate('Y-m-d', strtotime($data['attendance_date']));
        }else{
            $attendance_date = gmdate('Y-m-d', $this->serverTimeString);
        }
        if (isset($data['attendance_status_date']) && $data['attendance_status_date'] != NULL) {
            $attendance_status_date = gmdate('Y-m-d G:i:s', strtotime($data['attendance_status_date']));
        }else{
            $attendance_status_date = gmdate('Y-m-d G:i:s', $this->serverTimeString);
        }
        $user_id = app('loginUser')->getUser()->id;
        // $session_token_id = $this->sessionToken->getSessionToken();
        $session_token_id = 1;
        $created_at = gmdate('Y-m-d G:i:s', $this->serverTimeString);
        $updated_at = gmdate('Y-m-d G:i:s', $this->serverTimeString);
        $data['attendance_date'] = $attendance_date;
        $data['attendance_status_date'] = $attendance_status_date;
        $data['user_id'] = $user_id;
        $data['session_token_id'] = $session_token_id;
        $data['created_by'] = $user_id;
        $data['last_modified_by'] = $user_id;
        $data['deleted_by'] = NULL;
        $data['created_at'] = $created_at;
        $data['updated_at'] = $updated_at;
        
        // $data = [
        //     'user_id' => 1,
        //     'session_token_id' => '1',
        //     'attendance_date' => gmdate('Y-m-d', strtotime('2022-02-02')),
        //     'attendance_status' => 'I',
        //     'attendance_status_date' => gmdate('Y-m-d h:i:s', strtotime('2022-02-02 6:00')),
        //     'status' => 'A',
        //     'created_by' => 1,
        //     'last_modified_by' => NULL,
        //     'deleted_by' => NULL,               
        // ];

        try {
            // check if already checkin or checkout
            // two consecutive check-ins or check-outs not allowed for a user
            $previousEntry = $this->attendance->getPreviousEntry($data);
            $isvalid = $this->validateConsecutiveEntry($data, $previousEntry, $response);
            if($isvalid && $previousEntry){
                // check-in and check-out is on different dates
                // if last status = I, and date changed, that means I and O are on different dates
                // user has to checkout first. from today's date but
                // attendance_date would be the last_status date
                // I=Checkin, O=Checkout 
                $this->adjustIODates($previousEntry, $data);
            }

            if ($isvalid) {
                
                $insertedRecord = $this->attendanceLog->saveRecord($data);
                if ($insertedRecord) {
                    $attendanceLog = $insertedRecord;
                    // now save activity log with type CI or CO
                    $activityData = [
                        'user_id' => $attendanceLog->user_id,
                        'session_token_id' => $attendanceLog->session_token_id,
                        'activity_date' => $attendanceLog->attendance_status_date,
                        'log_from_date' => NULL,
                        'log_to_date' => NULL,
                        'note'  =>  NULL,
                        'keyboard_track' => NULL,
                        'mouse_track'   => NULL,
                        'time_type' => $attendanceLog->attendance_status == 'I' ? 'CI' : 'CO',
                        'created_by' => app('loginUser')->getUser()->id,
                        'last_modified_by' => app('loginUser')->getUser()->id,
                        'deleted_by' => NULL,
                        'created_at' => $created_at,
                        'updated_at' => $updated_at
                    ];
                    $result = $this->activityLog->saveRecord($activityData);
                    if ($result) {
                        $response['success'] = 1;
                        $response['data'] = $insertedRecord;
                    }
                }
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

    public function validateConsecutiveEntry($entry, $previousEntry, &$response)
    {
        // First case when no record, and two consecutive cases handled here
        $isvalid = false;
        if ($previousEntry) {
            if ($entry['attendance_status'] == 'I' && $previousEntry->last_attendance_status == $entry['attendance_status']) {
                $response['data'] = "You have already checked-in, Please checkout first";
            } elseif ($entry['attendance_status'] == 'O' && $previousEntry->last_attendance_status == $entry['attendance_status']) {
                $response['data'] = "You have already checked-out, Please check-in first";
            } else {
                $isvalid = true;
            }
        } else {
            if ((!$previousEntry || $previousEntry == null) && $entry['attendance_status'] == 'O') {
                $response['data'] = "Please check-in first!";
            } else {
                $isvalid = true;
            }
        }
        return $isvalid;
    }

    public function adjustIODates($previousEntry, &$data)
    {
        // Modify attendance date if checkin and checkout are on different dates
        $lastStatus = $previousEntry->last_attendance_status;
        if($lastStatus == 'I'){
            $data['attendance_date'] = $previousEntry->attendance_date;
        }
    }

    public static function getAttendanceLogs()
    {
        return "Attendanc logs returned";
    }
}
