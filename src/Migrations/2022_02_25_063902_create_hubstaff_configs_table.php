<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateHubstaffConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hubstaff_configs', function (Blueprint $table) {
            $table->id();
            $table->integer('screenshot_frequency');
            $table->integer('idle_timeout');
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
        Schema::dropIfExists('hubstaff_configs');
    }
}
