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
        Schema::create('node_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('root_dir_id')->index();
            $table->unsignedBigInteger('node_id')->index();
            $table->unsignedBigInteger('old_parent_id')->nullable(true)->index();
            $table->tinyInteger('opcode')->default(1)->index();
            $table->string('old_name', 192)->nullable(true);
            $table->string('new_name', 192)->nullable(true);
            $table->unsignedBigInteger('slice_offset')->nullable(true);
            $table->unsignedInteger('slice_size')->nullable(true);
            $table->string('slice_file', 1024)->nullable(true);
            $table->string('finger_print', 64)->nullable(true);
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
        Schema::dropIfExists('node_logs');
    }
};
