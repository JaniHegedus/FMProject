<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vote_to_skips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listener_id')->constrained('listeners');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vote_to_skips');
    }
};
