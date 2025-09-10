// vite.config.js
import { defineConfig, loadEnv } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), "");
    const disableOverlay = env.VITE_DISABLE_HMR_OVERLAY === "1";

    return {
        plugins: [
            laravel({
                input: [
                    // Admin bundles
                    "resources/css/app.css",
                    "resources/js/app.js",

                    // Theme bundles
                    "resources/themes/chainlite/theme.css",
                    "resources/themes/chainlite/theme.js",
                ],
                // Watch theme blades & assets explicitly
                refresh: [
                    "routes/**",
                    "app/Http/Controllers/**",
                    "resources/views/**",
                    "resources/themes/**",
                ],
            }),
            tailwindcss(), // Tailwind v4 plugin
        ],
        server: {
            hmr: {
                // Set VITE_DISABLE_HMR_OVERLAY=1 in .env to hide the red overlay temporarily
                overlay: !disableOverlay,
            },
        },
        css: {
            devSourcemap: true, // handy during styling
        },
    };
});
