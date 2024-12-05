<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MakeService extends Command
{
    protected $signature = 'make:service {name}';
    protected $description = 'Create a new service class';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $path = app_path("Services/{$name}.php");

        if (file_exists($path)) {
            $this->error("Service {$name} already exists!");
            return;
        }

        $stub = "<?php\n\nnamespace App\Services;\n\nclass {$name}\n{\n    // Your methods here\n}";
        file_put_contents($path, $stub);
        $this->info("Service {$name} created successfully!");
    }
}
