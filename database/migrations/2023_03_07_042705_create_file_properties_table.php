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
        Schema::create('file_properties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('root_dir_id')->index();
            $table->string('name', 1024)->index();
            $table->string('description', 1024)->nullable(true);
            $table->unsignedBigInteger('renamed_file_id')->nullable(true);
            $table->timestamps();
            $table->datetime('deleted_at')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('file_properties');
    }
};
