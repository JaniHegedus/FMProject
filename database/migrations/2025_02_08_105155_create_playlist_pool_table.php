<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlaylistPoolTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('playlist_pool', function (Blueprint $table) {
            $table->id();
            $table->string('video_id');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            // Optionally, if you want to enforce a foreign key constraint:
            // $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('playlist_pool');
    }
}
