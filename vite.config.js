import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    base: '/',
    plugins: [
        laravel({
            input: ['resources/js/auto-import.js',
                'resources/js/app.js'],
            refresh: true,
        }),
    ],
});
