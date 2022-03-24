<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAttendancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->date('attendance_date');
            $table->enum('last_attendance_status', ['I', 'O']);
            $table->bigInteger('last_attendance_id');
            $table->bigInteger('hours');
            $table->enum('status', ['A', 'I']);
            $table->bigInteger('created_by')->nullable();
            $table->bigInteger('last_modified_by')->nullable();
            $table->bigInteger('deleted_by')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->default(DB::raw('NULL ON UPDATE CURRENT_TIMESTAMP'))->nullable();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendances');
    }
}
