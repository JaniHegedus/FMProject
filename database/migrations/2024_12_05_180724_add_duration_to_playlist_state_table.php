<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDurationToPlaylistStateTable extends Migration
{
    public function up()
    {
        Schema::table('playlist_state', function (Blueprint $table) {
            $table->integer('duration')->after('start_time')->nullable()->comment('Duration of the video in seconds');
        });
    }

    public function down()
    {
        Schema::table('playlist_state', function (Blueprint $table) {
            $table->dropColumn('duration');
        });
    }
}
