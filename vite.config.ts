import {defineConfig} from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    build: {
        rollupOptions: {
            output: {
                assetFileNames: '[name][extname]',
                chunkFileNames: `[name].js`,
                entryFileNames: '[name].js',
            },
        },
    },
    plugins: [
        laravel({
            hotFile: 'public/vendor/glimpse/glimpse.hot',
            input: [
                'resources/js/glimpse.js',
                'resources/css/glimpse.css',
            ],
            refresh: [
                'resources/views/**/*.blade.php',
                'resources/css/**/*.css',
                'resources/js/**/*.js',
            ],
            buildDirectory: 'vendor/glimpse',
        }),
    ],
});
