<?php

namespace Insyghts\Hubstaff\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Insyghts\Common\Models\BaseModel;

class ActivityScreenShot extends Model
{
    use HasFactory, SoftDeletes;
    protected $guarded = [];

    public function saveRecord($bulk_data)
    {
        $result = false;
        $result = ActivityScreenShot::Insert($bulk_data);
        return $result;
    }
}
