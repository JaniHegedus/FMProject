#!/bin/bash

# Start Laravel development server
php artisan serve --host=0.0.0.0 --port=8000 &

# Start Vite (assumes Vite is installed and configured in your project)
npm run dev &

# Start Laravel scheduler
php artisan schedule:run --no-interaction &

# Wait indefinitely to keep the container running
tail -f /dev/null
