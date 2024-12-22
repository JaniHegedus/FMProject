<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('playlist_videos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('playlist_state_id')->nullable();

            $table->string('video_id')->unique();    // e.g. "abc123XYZ"
            $table->string('title')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->string('thumbnail_url')->nullable();

            $table->timestamps();

            // Foreign key to playlist_state
            $table->foreign('playlist_state_id')
                ->references('id')
                ->on('playlist_state')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('playlist_videos');
    }
};
