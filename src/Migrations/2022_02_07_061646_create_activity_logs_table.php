<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateActivityLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->bigInteger('session_token_id');
            $table->dateTime('activity_date');
            $table->dateTime('log_from_date')->nullable();
            $table->dateTime('log_to_date')->nullable();
            $table->string('note')->nullable();
            $table->bigInteger('keyboard_track')->nullable();
            $table->bigInteger('mouse_track')->nullable();
            $table->enum('time_type', ['N', 'I', 'CI', 'CO']);
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
        Schema::dropIfExists('activity_logs');
    }
}
