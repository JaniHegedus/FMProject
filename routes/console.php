<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('update_playlist', function () {
    // Explicitly register your command class
    $this->call('playlist:update');

})->describe('Update the playlist');

Artisan::command('rotate {--force}', function () {
    // Retrieve the force option from this command
    $force = $this->option('force');
    // Call the 'playlist:rotate' command with the force option
    $this->call('playlist:rotate', ['--force' => $force]);
})->describe('Rotate the playlist');


Artisan::command('play {title} {name}', function ($title, $name) {
    $this->call(\App\Console\Commands\PlayVideo::class, [
        'title' => $title,
        'name'  => $name,
    ]);
})->describe('Play a song from the playlist Title: Name:');
