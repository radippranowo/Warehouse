import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'node:path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
        },
    },
    build: {
        chunkSizeWarningLimit: 700,
        rolldownOptions: {
            output: {
                // Split vendor & framework dari kode aplikasi supaya cache lebih efisien.
                manualChunks(id) {
                    if (!id.includes('node_modules')) return;
                    if (/[\\/]node_modules[\\/](@vue|vue)[\\/]/.test(id)) return 'vue';
                    if (/[\\/]node_modules[\\/]@inertiajs[\\/]/.test(id)) return 'inertia';
                    return 'vendor';
                },
            },
        },
    },
});
