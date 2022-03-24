<?php

namespace Insyghts\Hubstaff\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Insyghts\Common\Models\BaseModel;

class AttendanceLog extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'attendance_logs';
    protected $guarded = [];
    
    public function saveRecord($data)
    {
        $inserted = false;
        // $attendanceLogs = [];
        // array_push($attendanceLogs, $data);
        $id = AttendanceLog::insertGetId($data);
        if ($id) {
            $inserted = AttendanceLog::find($id);
        }
        return $inserted;
    }

    public function getUserAttendanceLogsByDate($user_id, $attendance_date)
    {
        $checkInLogs = AttendanceLog::where('user_id', '=', $user_id)
            ->where('attendance_date', '=', gmdate('Y-m-d', strtotime($attendance_date)))
            ->where('attendance_status', '=', 'I')
            ->get();
        $checkOutLogs = AttendanceLog::where('user_id', '=', $user_id)
            ->where('attendance_date', '=', gmdate('Y-m-d', strtotime($attendance_date)))
            ->where('attendance_status', '=', 'O')
            ->get();
        return [
            'checkin_logs' => $checkInLogs->toArray(),
            'checkout_logs' => $checkOutLogs->toArray(),
        ];
    }
}
