<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('video_datas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('playlist_video_id');

            $table->text('description')->nullable();
            $table->string('status')->nullable();
            $table->string('duration')->nullable(); // e.g. "PT4M32S"
            $table->timestamps();

            // Foreign key to playlist_videos
            $table->foreign('playlist_video_id')
                ->references('id')
                ->on('playlist_videos')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('video_datas');
    }
};
