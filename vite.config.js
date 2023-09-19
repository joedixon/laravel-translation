import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            buildDirectory: '../dist', 
            input: ['resources/css/translation.css'],
            refresh: true,
        }),
    ],
});
