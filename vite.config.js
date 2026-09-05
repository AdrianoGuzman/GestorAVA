import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import {
    defineConfig
} from 'vite';
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            ssr: 'resources/js/ssr.jsx',
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    server: {
        strictPort: true,
        host: "0.0.0.0",
        port: 3000,
        hmr: {
            host: "localhost",
            protocol: "ws",
            clientPort: 3000,
        },
        watch: {
            usePolling: true,
            interval: 300,
            ignored: [
                "**/node_modules/**",
                "**/.git/**",
                "**/vendor/**",
                "**/storage/**",
                "**/bootstrap/cache/**",
                "**/.env*",
            ],
        },
    },
    esbuild: {
        jsx: 'automatic',
    },
});