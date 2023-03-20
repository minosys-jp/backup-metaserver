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
        Schema::create('check_points', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('root_dir_id')->index();
            $table->tinyInteger('flg_create')->default(0);
            $table->string('cp_file_name', 1024);
            $table->text('description')->nullable(true);
            $table->datetime('when');
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
        Schema::dropIfExists('check_points');
    }
};
