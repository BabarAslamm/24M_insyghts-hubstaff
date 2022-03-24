<?php

namespace Insyghts\Hubstaff\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Insyghts\Common\Models\BaseModel;

class Attendance extends Model
{
    use HasFactory, SoftDeletes;

    public function getAttendanceList($filters=[])
    {
        $queryObj = Attendance::query();
        $queryObj->orderBy('id', 'DESC');
        $limit = !empty($filters['limit']) ? $filters['limit'] : 30;
        if(count($filters) > 0){
            foreach($filters as $filter){
                if($filter['condition'] == 'like' || $filter['condition'] == 'LIKE'){
                    $queryObj->where($filter['key'], $filter['condition'], "%{$filter['value']}%");
                }else{
                    $queryObj->where($filter['key'], $filter['condition'], $filter['value']);
                }
            }
        }
        $result = $queryObj->paginate($limit);
        return $result;
    }

    public function getPreviousEntry($entry)
    {
        $previousEntry = Attendance::where('user_id', '=', ((int)$entry['user_id']))
            ->orderBy('id', 'DESC')->first();
        return $previousEntry;
    }

    public function saveAttendance($attendance)
    {
        $inserted=false;
         
        $inserted = $attendance->save();

        return $inserted;
    }

    public function getAttendanceById($id)
    {
        return Attendance::find($id);
    }

    public function getAttendanceByUser($user_id)
    {
        return Attendance::where('user_id', '=', ((int)$user_id))->get();
    }

    public function getAttendanceByDate($attendance_date)
    {
        return Attendance::where('attendance_date', '=', gmdate('Y-m-d', strtotime($attendance_date)))
                ->get();
    }

    public function getAttendanceByUserAndDate($user_id, $attendance_date){
        return Attendance::where('user_id', '=', $user_id)
                    ->where('attendance_date', $attendance_date)->first();
    }

    public function getLastAttendance($user_id)
    {
        return Attendance::where('user_id', '=', ((int)$user_id))
                ->orderBy('id', 'DESC')->first();
    }
}
