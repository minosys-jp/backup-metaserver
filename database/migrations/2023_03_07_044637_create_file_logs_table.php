<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('file_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('file_slice_id')->index();
            $table->unsignedBigInteger('file_property_id')->index();
            // the last slice numer of the current transfer
            $table->integer('last_slice_number')->default(0);
            $table->integer('start_slice_number')->default(0);
            $table->integer('max_slice_number')->default(0);
            $table->integer('slice_number')->default(0);
            $table->unsignedBigInteger('slice_offset')->default(0);
            // slice_size == 0 means 'file-slice delted'
            $table->unsignedBigInteger('slice_size')->default(2 * 1024 * 1024);
            $table->unsignedBigInteger('prev_log_id')->nullable(true);
            $table->string('finger_print', 128)->nullable(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('file_logs');
    }
};
