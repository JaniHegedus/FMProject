<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVotedByToPlaylistPoolTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('playlist_pool', function (Blueprint $table) {
            // Add a JSON column that will hold an array of user IDs that have voted for the entry.
            $table->json('voted_by')->nullable()->after('votes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('playlist_pool', function (Blueprint $table) {
            $table->dropColumn('voted_by');
        });
    }
}
